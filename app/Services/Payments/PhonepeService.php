<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentAttempt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class PhonepeService
{
    private string $clientId;

    private string $clientSecret;

    private int $clientVersion;

    private string $baseUrl;

    private string $env;

    private string $merchantId;

    public function __construct()
    {
        $this->clientId = (string) config('services.phonepe.client_id');
        $this->clientSecret = (string) config('services.phonepe.client_secret');
        $this->clientVersion = (int) (config('services.phonepe.client_version') ?? 1);
        $this->merchantId = (string) config('services.phonepe.merchant_id');
        $this->env = (string) config('services.phonepe.env', 'UAT');

        // Initialize baseUrl based on environment
        $this->baseUrl = $this->env === 'PROD'
            ? 'https://api.phonepe.com/apis/pg'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';

        if ($this->clientId === '' || $this->clientId === '0' || ($this->clientSecret === '' || $this->clientSecret === '0')) {
            throw new RuntimeException('PhonePe credentials (client_id or client_secret) are missing in config.');
        }

        if ($this->merchantId === '' || $this->merchantId === '0') {
            throw new RuntimeException('PhonePe merchant_id is missing in config.');
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

        // Set auth URL based on environment
        $authUrl = $this->env === 'PROD'
            ? 'https://api.phonepe.com/apis/identity-manager'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';

        // Generate new token
        $response = Http::asForm()->post($authUrl . '/v1/oauth/token', [
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

        $transactionId = 'MT-' . $attempt->id . '-' . Str::random(6);
        $amountInPaise = (int) round($attempt->grand_total * 100);

        $frontendUrl = config('app.frontend_url');
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

        // Store PhonePe order ID in attempt metadata
        $attempt->update([
            'payment_gateway_order_id' => $transactionId,
            'metadata' => array_merge($attempt->metadata ?? [], [
                'phonepe_order_id' => $orderId,
            ]),
        ]);

        return [
            'gateway' => 'phonepe',
            'order_id' => $transactionId,
            'phonepe_order_id' => $orderId,
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'url' => $redirectUrl,
            'state' => $resData['state'] ?? 'PENDING',
            'expires_at' => $resData['expireAt'] ?? null,
        ];
    }

    public function verifyAndCapture(array $payload): array
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
    /**
     * Generate PhonePe V2 token for React Native SDK
     *
     * V2 DOES NOT use base64###checksum format
     * According to PhonePe support: "base64 encoding is not needed in V2"
     *
     * @param string $merchantTransactionId Unique transaction identifier
     * @param int $amount Amount in paise
     * @param string $userId User identifier
     * @param string $userMobile User mobile number
     * @param int $orderId Order ID from database
     * @return string V2 token for PhonePe SDK
     */
    public function generateChecksum(
        string $merchantTransactionId,
        int $amount,
        string $userId,
        string $userMobile,
        int $orderId = null
    ): string {
        try {
            // V2: Create a JSON object with required parameters
            // NO base64 encoding according to PhonePe support
            $payload = [
                'merchantId' => config('services.phonepe.merchant_id'),
                'merchantTransactionId' => $merchantTransactionId,
                'merchantUserId' => $userId,
                'amount' => $amount,
                'redirectUrl' => config('app.frontend_url') . '/payment/success',
                'redirectMode' => 'POST',
                'callbackUrl' => config('app.api_url') . '/api/v1/payment/phonepe-webhook',
                'mobileNumber' => $userMobile,
                'paymentInstrument' => [
                    'type' => 'PAY_PAGE'
                ]
            ];

            // V2: Return JSON string directly, NOT base64 encoded
            $token = json_encode($payload);

            if (config('app.debug')) {
                \Log::info('PhonePe V2 Token Generated (No Base64):', [
                    'payload' => $payload,
                    'json_token' => $token
                ]);
            }

            return $token;
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to generate V2 token: ' . $e->getMessage());
        }
    }


    /**
     * Verify transaction status with PhonePe API
     *
     * @param string $merchantTransactionId Transaction ID
     * @param int $orderId Order ID for reference
     * @return array Verification result
     */
    public function verifyTransaction(string $merchantTransactionId, int $orderId): array
    {
        try {
            $token = $this->getAccessToken();

            $path = sprintf('/checkout/v2/order/%s/status', $merchantTransactionId);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'O-Bearer ' . $token,
            ])->get($this->baseUrl . $path);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to verify transaction with PhonePe API',
                    'status_code' => $response->status(),
                ];
            }

            $resData = $response->json();
            $state = $resData['state'] ?? '';

            // Check payment success state
            if ($state !== 'COMPLETED' && $state !== 'PAYMENT_SUCCESS') {
                return [
                    'success' => false,
                    'message' => 'Payment Validation Failed: ' . ($resData['message'] ?? 'State: ' . $state),
                    'state' => $state,
                ];
            }

            // Extract transaction details
            $phonePeTransactionId = $resData['paymentDetails'][0]['transactionId']
                ?? $resData['orderId']
                ?? $merchantTransactionId;

            $order = Order::findOrFail($orderId);

            // Check if paid
            if ($order->status === 'paid') {
                return [
                    'success' => true,
                    'message' => 'Order already paid',
                    'order_id' => $order->id,
                ];
            }

            // Create payment record
            OrderPayment::create([
                'order_id' => $order->id,
                'amount' => $order->grand_total,
                'payment_method' => 'phonepe',
                'transaction_id' => $phonePeTransactionId,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Update order status
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Payment verified and captured successfully',
                'order_id' => $order->id,
                'transaction_id' => $phonePeTransactionId,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Transaction verification failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create Order Token for SDK integration (React Native / Web)
     * Uses the new v2/sdk/order endpoint
     */
    public function createOrderToken(int $orderId, int $amountInPaise, array $metaInfo = []): array
    {
        try {
            $token = $this->getAccessToken();

            $merchantOrderId = 'TX' . time() . $orderId;

            $payload = [
                'merchantOrderId' => $merchantOrderId,
                'amount' => $amountInPaise,
                'expireAfter' => 1200, // 20 minutes
                'metaInfo' => array_merge([
                    'udf1' => (string) $orderId,
                    'udf2' => '',
                    'udf3' => '',
                    'udf4' => '',
                    'udf5' => '',
                ], $metaInfo),
                'paymentFlow' => [
                    'type' => 'PG_CHECKOUT',
                ],
            ];

            $endpoint = $this->baseUrl . '/checkout/v2/sdk/order';

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'O-Bearer ' . $token,
            ])->post($endpoint, $payload);

            if (!$response->successful()) {
                throw new RuntimeException('PhonePe Create Order Token Error: ' . $response->body());
            }

            $resData = $response->json();

            return [
                'success' => true,
                'orderId' => $resData['orderId'] ?? null,
                'merchantOrderId' => $merchantOrderId,
                'state' => $resData['state'] ?? 'PENDING',
                'expireAt' => $resData['expireAt'] ?? null,
                'token' => $resData['token'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create order token: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check Order Status by merchant order ID
     */
    public function checkOrderStatus(string $merchantOrderId, bool $details = false, bool $errorContext = true): array
    {
        try {
            $token = $this->getAccessToken();

            $endpoint = $this->baseUrl . '/checkout/v2/order/' . $merchantOrderId . '/status';

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'O-Bearer ' . $token,
            ])->get($endpoint, [
                'details' => $details ? 'true' : 'false',
                'errorContext' => $errorContext ? 'true' : 'false',
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to check order status',
                    'status_code' => $response->status(),
                ];
            }

            $resData = $response->json();

            return [
                'success' => true,
                'data' => $resData,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Order status check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Initiate Refund
     */
    public function initiateRefund(string $merchantRefundId, string $originalMerchantOrderId, int $amountInPaise): array
    {
        try {
            $token = $this->getAccessToken();

            $payload = [
                'merchantRefundId' => $merchantRefundId,
                'originalMerchantOrderId' => $originalMerchantOrderId,
                'amount' => $amountInPaise,
            ];

            $endpoint = $this->baseUrl . '/payments/v2/refund';

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'O-Bearer ' . $token,
            ])->post($endpoint, $payload);

            if (!$response->successful()) {
                throw new RuntimeException('PhonePe Refund Initiation Error: ' . $response->body());
            }

            $resData = $response->json();

            return [
                'success' => true,
                'refundId' => $resData['refundId'] ?? null,
                'amount' => $resData['amount'] ?? null,
                'state' => $resData['state'] ?? 'PENDING',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to initiate refund: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check Refund Status
     */
    public function checkRefundStatus(string $merchantRefundId): array
    {
        try {
            $token = $this->getAccessToken();

            $endpoint = $this->baseUrl . '/payments/v2/refund/' . $merchantRefundId . '/status';

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'O-Bearer ' . $token,
            ])->get($endpoint);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to check refund status',
                    'status_code' => $response->status(),
                ];
            }

            $resData = $response->json();

            return [
                'success' => true,
                'data' => $resData,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Refund status check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle Webhook Callback (new event-based format)
     */
    public function handleWebhook(array $payload): array
    {
        try {
            $event = $payload['event'] ?? null;
            $payloadData = $payload['payload'] ?? [];

            if (!$event) {
                throw new RuntimeException('Webhook event type not found');
            }

            $orderId = $payloadData['orderId'] ?? null;
            $merchantOrderId = $payloadData['merchantOrderId'] ?? null;
            $state = $payloadData['state'] ?? null;

            // Extract order ID from merchant order ID (format: TX{timestamp}{orderId})
            if ($merchantOrderId && preg_match('/TX\d+(\d+)$/', $merchantOrderId, $matches)) {
                $extractedOrderId = (int) $matches[1];
            }

            $result = [
                'success' => true,
                'event' => $event,
                'orderId' => $orderId,
                'merchantOrderId' => $merchantOrderId,
                'state' => $state,
                'extractedOrderId' => $extractedOrderId ?? null,
            ];

            // Handle different event types
            if ($event === 'checkout.order.completed' && $state === 'COMPLETED') {
                // Payment successful - update order
                if (isset($extractedOrderId)) {
                    $order = Order::find($extractedOrderId);
                    if ($order && $order->status !== 'paid') {
                        $transactionId = $payloadData['paymentDetails'][0]['transactionId'] ?? $orderId;

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

                        $result['order_updated'] = true;
                    }
                }
            }

            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Webhook processing failed: ' . $e->getMessage(),
            ];
        }
    }
}
