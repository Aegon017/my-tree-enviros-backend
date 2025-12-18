<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class PhonepeService
{
    private string $clientId;

    private string $clientSecret;

    private int $clientVersion;

    private string $baseUrl;

    private string $env;

    public function __construct()
    {
        $this->clientId = (string) config('services.phonepe.client_id');
        $this->clientSecret = (string) config('services.phonepe.client_secret');
        $this->clientVersion = (int) (config('services.phonepe.client_version') ?? 1);
        $this->env = (string) config('services.phonepe.env', 'UAT');

        if ($this->clientId === '' || $this->clientId === '0' || ($this->clientSecret === '' || $this->clientSecret === '0')) {
            throw new RuntimeException('PhonePe credentials (client_id or client_secret) are missing in config.');
        }

        $this->baseUrl = $this->env === 'PROD'
            ? 'https://api.phonepe.com/apis/pg'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
    }

    public function createGatewayOrder(Order $order): array
    {
        $token = $this->getAccessToken();

        $transactionId = 'MT-' . $order->id . '-' . Str::random(6);
        $amountInPaise = (int) round($order->grand_total * 100);

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $callbackUrl = $frontendUrl . ('/payment/phonepe-callback?merchantOrderId=' . $transactionId);

        $payload = [
            'merchantOrderId' => $transactionId,
            'amount' => $amountInPaise,
            'paymentFlow' => [
                'type' => 'PG_CHECKOUT',
                'merchantUrls' => [
                    'redirectUrl' => $callbackUrl,
                ],
            ],
        ];

        $endpoint = $this->baseUrl . '/checkout/v2/pay';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'O-Bearer ' . $token,
        ])->post($endpoint, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('PhonePe API Error: ' . $response->body());
        }

        $resData = $response->json();

        $redirectUrl = $resData['redirectUrl'] ?? null;
        $orderId = $resData['orderId'] ?? null;

        if (! $redirectUrl) {
            throw new RuntimeException('PhonePe Redirect URL not found in V2 response');
        }

        return [
            'gateway' => 'phonepe',
            'order_id' => $transactionId,
            'phonepe_order_id' => $orderId,
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'url' => $redirectUrl,
            'env' => $this->env,
        ];
    }

    public function verifyAndCapture(array $payload): array
    {
        $merchantTransactionId = $payload['razorpay_order_id'] ?? $payload['transaction_id'] ?? '';

        if (empty($merchantTransactionId)) {
            throw new RuntimeException('Transaction ID missing for verification');
        }

        $token = $this->getAccessToken();

        $path = sprintf('/checkout/v2/order/%s/status', $merchantTransactionId);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'O-Bearer ' . $token,
        ])->get($this->baseUrl . $path);

        $resData = $response->json();

        $state = $resData['state'] ?? '';

        if ($state !== 'COMPLETED' && $state !== 'PAYMENT_SUCCESS') {
            throw new RuntimeException('Payment Validation Failed: ' . ($resData['message'] ?? 'State: ' . $state));
        }

        $parts = explode('-', (string) $merchantTransactionId);
        if (count($parts) < 2) {
            throw new RuntimeException('Invalid Transaction ID format');
        }

        $orderId = (int) $parts[1];

        $order = Order::findOrFail($orderId);

        if ($order->status === 'paid') {
            return [
                'success' => true,
                'order_id' => $order->id,
                'reference_number' => $order->reference_number,
            ];
        }

        $phonePeTransactionId = $resData['paymentDetails'][0]['transactionId'] ?? $resData['orderId'] ?? $merchantTransactionId;

        OrderPayment::create([
            'order_id' => $order->id,
            'amount' => $order->grand_total,
            'payment_method' => 'phonepe', // Or strtolower($paymentMode)
            'transaction_id' => $phonePeTransactionId,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $order->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return [
            'success' => true,
            'order_id' => $order->id,
            'reference_number' => $order->reference_number,
        ];
    }

    private function getAccessToken(): string
    {
        $authUrl = $this->env === 'PROD'
            ? 'https://api.phonepe.com/apis/identity-manager/v1/oauth/token'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token';

        $response = Http::asForm()->post($authUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
            'client_version' => $this->clientVersion,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('PhonePe Auth Error: ' . $response->body());
        }

        return $response->json()['access_token'];
    }
}
