<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentAttempt;
use App\Models\User;
use App\Notifications\OrderPaidNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class PhonepeService
{
    private StandardCheckoutClient $client;

    public function __construct()
    {
        $clientId = (string) config('services.phonepe.client_id');
        $clientSecret = (string) config('services.phonepe.client_secret');
        $clientVersion = (int) (config('services.phonepe.client_version') ?? 1);
        $env = config('services.phonepe.env', 'UAT') === 'PROD' ? Env::PRODUCTION : Env::UAT;

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('PhonePe credentials (client_id or client_secret) are missing in config.');
        }

        $this->client = StandardCheckoutClient::getInstance(
            $clientId,
            $clientVersion,
            $clientSecret,
            $env
        );
    }

    public function createGatewayOrder(PaymentAttempt $attempt): array
    {
        $token = $this->getAccessToken();

        $transactionId = 'MT-' . $attempt->id . '-' . Str::random(6);
        $amountInPaise = (int) round($attempt->grand_total * 100);

        $backendUrl = config('app.api_url');
        $callbackUrl = $backendUrl . '/api/v1/payment/callback?merchantOrderId=' . $transactionId;

        try {
            $payRequest = StandardCheckoutPayRequestBuilder::builder()
                ->merchantOrderId($merchantOrderId)
                ->amount($amountInPaise)
                ->redirectUrl($redirectUrl)
                ->message('Order payment for session ' . $session->id)
                ->build();

            $payResponse = $this->client->pay($payRequest);

            if ($payResponse->getState() !== 'PENDING') {
                throw new RuntimeException('Payment initiation failed: ' . $payResponse->getState());
            }

            return [
                'gateway' => 'phonepe',
                'order_id' => $merchantOrderId,
                'phonepe_order_id' => $payResponse->getOrderId(),
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'url' => $payResponse->getRedirectUrl(),
                'state' => $payResponse->getState(),
                'expires_at' => $payResponse->getExpireAt(),
            ];
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            throw new RuntimeException('PhonePe SDK Error: ' . $e->getMessage(), 0, $e);
        }

        // Store gateway order ID in attempt
        $attempt->update(['payment_gateway_order_id' => $orderId]);

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

    public function verifyOrderStatus(string $merchantOrderId): array
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

        $attemptId = (int) $parts[1];

        $attempt = PaymentAttempt::findOrFail($attemptId);

        // Check if already converted to order
        if ($attempt->status === 'completed' && $attempt->created_order_id) {
            $order = Order::find($attempt->created_order_id);
            return [
                'success' => true,
                'merchant_order_id' => $merchantOrderId,
                'phonepe_order_id' => $statusResponse->getOrderId(),
                'state' => $statusResponse->getState(),
                'amount' => $statusResponse->getAmount(),
                'payment_details' => $statusResponse->getPaymentDetails(),
                'error_code' => $statusResponse->getErrorCode(),
            ];
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }

        // Convert attempt to order
        $order = app(PaymentAttemptService::class)->convertToOrder($attempt);

        $phonePeTransactionId = $resData['paymentDetails'][0]['transactionId'] ?? $resData['orderId'] ?? $merchantTransactionId;

        OrderPayment::create([
            'order_id' => $order->id,
            'amount' => $order->grand_total,
            'payment_method' => 'phonepe',
            'transaction_id' => $phonePeTransactionId,
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

    /**
     * Generate checksum token for frontend payment initiation
     *
     * @param string $merchantTransactionId Unique transaction identifier
     * @param int $amount Amount in paise
     * @param string $userId User identifier
     * @param string $userMobile User mobile number
     * @param int $orderId Order ID from database
     * @return string Encoded token for PhonePe SDK
     */
    public function generateChecksum(
        string $merchantTransactionId,
        int $amount,
        string $userId,
        string $userMobile,
        int $orderId = null
    ): string {
        try {
            $payload = [
                'merchantId' => config('services.phonepe.merchant_id'),
                'merchantTransactionId' => $merchantTransactionId,
                'merchantUserId' => $userId,
                'amount' => $amount,
                'callbackUrl' => config('app.api_url') . '/api/v1/payment/phonepe-webhook',
                'mobileNumber' => $userMobile,
                // FIX 1: Add the mandatory paymentInstrument
                'paymentInstrument' => [
                    'type' => 'PAY_PAGE'
                ]
            ];

            if (config('app.debug')) {
                \Log::info('PhonePe Payload:', $payload);
            }

            // Encode as base64
            $encodedPayload = base64_encode(json_encode($payload));

            // FIX 2: Use correct Checksum Logic (Concatenation, NOT HMAC)
            // Format: SHA256(Base64Body + Endpoint + SaltKey) + ### + SaltIndex
            $saltKey = $this->clientSecret; // Assuming client_secret holds your Salt Key
            $saltIndex = 1; // Usually 1, check your dashboard

            $stringToHash = $encodedPayload . '/pg/v1/pay' . $saltKey;
            $checksumHash = hash('sha256', $stringToHash);

            $checksum = $checksumHash . '###' . $saltIndex;

            // Return token format: encoded_payload###checksum###version
            return $encodedPayload . '###' . $checksum . '###1';
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to generate checksum: ' . $e->getMessage());
        }
    }

    public function initiateRefund(string $originalMerchantOrderId, int $amountInPaise, string $merchantRefundId): array
    {
        try {
            $refundRequest = StandardCheckoutRefundRequestBuilder::builder()
                ->merchantRefundId($merchantRefundId)
                ->originalMerchantOrderId($originalMerchantOrderId)
                ->amount($amountInPaise)
                ->build();

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

            $attempt = PaymentAttempt::findOrFail($orderId);

            // Check if already converted
            if ($attempt->status === 'completed' && $attempt->created_order_id) {
                $order = Order::find($attempt->created_order_id);
                return [
                    'success' => true,
                    'message' => 'Order already paid',
                    'order_id' => $order->id,
                ];
            }

            // Convert attempt to order
            $order = app(PaymentAttemptService::class)->convertToOrder($attempt);

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

            // Send notifications
            $order->user->notify(new OrderPaidNotification($order));
            $this->notifyAdmins(new OrderPaidNotification($order));

            return [
                'success' => true,
                'refund_id' => $refundResponse->getRefundId(),
                'state' => $refundResponse->getState(),
                'amount' => $refundResponse->getAmount(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function checkRefundStatus(string $merchantRefundId): array
    {
        try {
            $refundStatusResponse = $this->client->getRefundStatus($merchantRefundId);

            return [
                'success' => true,
                'refund_id' => $refundStatusResponse->getRefundId(),
                'state' => $refundStatusResponse->getState(),
                'amount' => $refundStatusResponse->getAmount(),
                'original_order_id' => $refundStatusResponse->getOriginalMerchantOrderId(),
                'payment_details' => $refundStatusResponse->getPaymentDetails(),
            ];
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function notifyAdmins($notification): void
    {
        $admins = User::role('super_admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, $notification);
        }
    }
}
