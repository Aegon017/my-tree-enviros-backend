<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentAttempt;
use App\Models\User;
use App\Notifications\OrderPaidNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class PhonepeService
{
    private string $clientId;
    private int $clientVersion;
    private string $clientSecret;
    private string $baseUrl;
    private string $authUrl;

    public function __construct()
    {
        $this->clientId = (string) config('services.phonepe.client_id');
        $this->clientVersion = (int) (config('services.phonepe.client_version') ?? 1);
        $this->clientSecret = (string) config('services.phonepe.client_secret');
        $env = config('services.phonepe.env', 'UAT');

        if ($env === 'PROD') {
            $this->authUrl = 'https://api.phonepe.com/apis/identity-manager';
            $this->baseUrl = 'https://api.phonepe.com/apis/pg';
        } else {
            $this->authUrl = 'https://api-preprod.phonepe.com/apis/pg-sandbox';
            $this->baseUrl = 'https://api-preprod.phonepe.com/apis/pg-sandbox';
        }

        if ($this->clientId === '' || $this->clientSecret === '') {
            throw new RuntimeException('PhonePe credentials (client_id or client_secret) are missing in config.');
        }
    }

    /**
     * Get OAuth access token (with caching)
     */
    private function getAccessToken(): string
    {
        $cacheKey = 'phonepe_access_token';

        // Try to get cached token
        $cachedToken = Cache::get($cacheKey);
        if ($cachedToken) {
            return $cachedToken;
        }

        // Generate new token
        $response = Http::asForm()->post($this->authUrl . '/v1/oauth/token', [
            'client_id' => $this->clientId,
            'client_version' => $this->clientVersion,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Failed to generate PhonePe access token: ' . $response->body());
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;
        $expiresAt = $data['expires_at'] ?? null;

        if (!$accessToken) {
            throw new RuntimeException('PhonePe access token not found in response');
        }

        // Cache token until 5 minutes before expiry
        $ttl = $expiresAt ? ($expiresAt - time() - 300) : 3600;
        Cache::put($cacheKey, $accessToken, $ttl);

        return $accessToken;
    }

    public function createGatewayOrder(PaymentAttempt $attempt): array
    {
        $token = $this->getAccessToken();
        $merchantOrderId = 'MT-' . $attempt->id . '-' . Str::random(6);
        $amountInPaise = (int) round($attempt->grand_total * 100);

        // redirectUrl is where user's browser goes after payment (frontend)
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $redirectUrl = $frontendUrl . '/payment/success?gateway=phonepe&order_id=' . $merchantOrderId;

        $payload = [
            'merchantOrderId' => $merchantOrderId,
            'amount' => $amountInPaise,
            'expireAfter' => 1800, // 30 minutes
            'paymentFlow' => [
                'type' => 'PG_CHECKOUT',
                'message' => 'Payment for Order #' . $attempt->id,
                'merchantUrls' => [
                    'redirectUrl' => $redirectUrl,
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'O-Bearer ' . $token,
        ])->post($this->baseUrl . '/checkout/v2/pay', $payload);

        if (!$response->successful()) {
            throw new RuntimeException('PhonePe API Error: ' . $response->body());
        }

        $resData = $response->json();

        if (!isset($resData['orderId'], $resData['redirectUrl'])) {
            throw new RuntimeException('Invalid PhonePe response: ' . json_encode($resData));
        }

        // Store PhonePe order ID in attempt
        $attempt->update([
            'payment_gateway_order_id' => $merchantOrderId,
            'metadata' => array_merge($attempt->metadata ?? [], [
                'phonepe_order_id' => $resData['orderId'],
            ]),
        ]);

        return [
            'gateway' => 'phonepe',
            'order_id' => $merchantOrderId,
            'phonepe_order_id' => $resData['orderId'],
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'url' => $resData['redirectUrl'],
            'state' => $resData['state'] ?? 'PENDING',
            'expires_at' => $resData['expireAt'] ?? null,
        ];
    }

    public function verifyOrderStatus(string $merchantOrderId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'O-Bearer ' . $token,
        ])->get($this->baseUrl . '/checkout/v2/order/' . $merchantOrderId . '/status', [
            'details' => 'false',
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Failed to verify PhonePe transaction: ' . $response->body());
        }

        $resData = $response->json();
        $state = $resData['state'] ?? '';

        if ($state !== 'COMPLETED') {
            throw new RuntimeException('Payment not completed. State: ' . $state);
        }

        // Extract attempt ID from merchant order ID (format: MT-{attemptId}-{random})
        $parts = explode('-', $merchantOrderId);
        if (count($parts) < 2) {
            throw new RuntimeException('Invalid merchant order ID format');
        }

        $attemptId = (int) $parts[1];
        $attempt = PaymentAttempt::findOrFail($attemptId);

        // Check if already converted to order
        if ($attempt->status === 'completed' && $attempt->created_order_id) {
            $order = Order::find($attempt->created_order_id);
            return [
                'success' => true,
                'order_id' => $order->id,
                'reference_number' => $order->reference_number,
                'already_processed' => true,
            ];
        }

        // Convert attempt to order
        $order = app(PaymentAttemptService::class)->convertToOrder($attempt);

        // Get transaction details
        $paymentDetails = $resData['paymentDetails'][0] ?? [];
        $transactionId = $paymentDetails['transactionId'] ?? $resData['orderId'] ?? $merchantOrderId;

        OrderPayment::create([
            'order_id' => $order->id,
            'amount' => $order->grand_total,
            'payment_method' => 'phonepe',
            'transaction_id' => $transactionId,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $order->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Send notifications
        $order->user->notify(new OrderPaidNotification($order));
        $this->notifyAdmins(new OrderPaidNotification($order));

        return [
            'success' => true,
            'order_id' => $order->id,
            'reference_number' => $order->reference_number,
        ];
    }

    private function notifyAdmins($notification): void
    {
        $admins = User::role('super_admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, $notification);
        }
    }
}
