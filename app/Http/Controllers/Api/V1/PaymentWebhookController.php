<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use App\Models\OrderPayment;
use App\Services\CheckoutSessionService;
use App\Services\Orders\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly CheckoutSessionService $checkoutSession,
        private readonly OrderService $orderService
    ) {}

    /**
     * Handle Razorpay webhook
     */
    public function razorpay(Request $request)
    {
        // Verify webhook signature
        $webhookSecret = config('services.razorpay.webhook_secret');
        $signature = $request->header('X-Razorpay-Signature');

        if ($webhookSecret && !$this->verifyRazorpaySignature($request->getContent(), $signature, $webhookSecret)) {
            Log::error('Invalid Razorpay webhook signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = $request->input('event');
        $payload = $request->input('payload.payment.entity');

        Log::info('Razorpay webhook received', ['event' => $event, 'order_id' => $payload['order_id'] ?? null]);

        if ($event === 'payment.captured') {
            $this->handlePaymentSuccess($payload);
        }

        if ($event === 'payment.failed') {
            $this->handlePaymentFailure($payload);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle PhonePe webhook using SDK
     */
    public function phonepe(Request $request)
    {
        // Get headers and request body
        $headers = $request->headers->all();
        $requestBody = $request->all();

        // Webhook credentials
        $username = config('services.phonepe.webhook_username');
        $password = config('services.phonepe.webhook_password');

        try {
            // Initialize PhonePe client
            $clientId = config('services.phonepe.client_id');
            $clientVersion = (int) config('services.phonepe.client_version', 1);
            $clientSecret = config('services.phonepe.client_secret');
            $env = config('services.phonepe.env', 'UAT') === 'PROD'
                ? \PhonePe\Env::PRODUCTION
                : \PhonePe\Env::UAT;

            $client = \PhonePe\payments\v2\standardCheckout\StandardCheckoutClient::getInstance(
                $clientId,
                $clientVersion,
                $clientSecret,
                $env
            );

            // Verify and parse callback using SDK
            $callbackResponse = $client->verifyCallbackResponse(
                $headers,
                $requestBody,
                $username,
                $password
            );

            // Get callback type and payload
            $callbackType = $callbackResponse->getType();
            $payload = $callbackResponse->getPayload();

            Log::info('PhonePe webhook received (SDK)', [
                'type' => $callbackType,
                'merchantOrderId' => $payload->getOriginalMerchantOrderId() ?? $payload->getMerchantOrderId() ?? null,
                'state' => $payload->getState(),
            ]);

            // Handle different callback types
            if ($callbackType === 'CHECKOUT_ORDER_COMPLETED') {
                $this->handlePhonePeSuccess([
                    'merchantOrderId' => $payload->getOriginalMerchantOrderId() ?? $payload->getMerchantOrderId(),
                    'orderId' => $payload->getOrderId(),
                    'state' => $payload->getState(),
                    'amount' => $payload->getAmount(),
                    'paymentDetails' => $payload->getPaymentDetails(),
                ]);
            }

            if ($callbackType === 'CHECKOUT_ORDER_FAILED') {
                $this->handlePhonePeFailure([
                    'merchantOrderId' => $payload->getOriginalMerchantOrderId() ?? $payload->getMerchantOrderId(),
                    'state' => $payload->getState(),
                    'errorCode' => $payload->getErrorCode(),
                    'detailedErrorCode' => $payload->getDetailedErrorCode(),
                    'paymentDetails' => $payload->getPaymentDetails(),
                ]);
            }

            return response()->json(['status' => 'ok'], 200);
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            Log::error('PhonePe webhook verification failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'http_status' => $e->getHttpStatusCode(),
            ]);
            return response()->json(['error' => 'Verification failed'], 400);
        } catch (\Exception $e) {
            Log::error('PhonePe webhook processing error', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Handle PhonePe successful payment
     */
    private function handlePhonePeSuccess(array $payload): void
    {
        $merchantOrderId = $payload['merchantOrderId'];
        $state = $payload['state'];

        // Verify state is COMPLETED
        if ($state !== 'COMPLETED') {
            Log::warning('PhonePe payment state not completed', [
                'merchantOrderId' => $merchantOrderId,
                'state' => $state,
            ]);
            return;
        }

        // Find checkout session by gateway order ID
        $session = $this->checkoutSession->findByGatewayOrderId($merchantOrderId);

        if (!$session) {
            Log::error('Checkout session not found for PhonePe payment', [
                'merchantOrderId' => $merchantOrderId,
            ]);
            return;
        }

        if ($session->status !== 'active') {
            Log::warning('Checkout session not active', [
                'session_id' => $session->id,
                'status' => $session->status,
            ]);
            return;
        }

        try {
            // Create order in transaction
            DB::transaction(function () use ($session, $payload) {
                // Create order with snapshots
                $order = $this->orderService->createOrderFromSession($session);

                // Extract payment details
                $paymentDetails = $payload['paymentDetails'][0] ?? [];
                $transactionId = $paymentDetails['transactionId'] ?? $payload['orderId'] ?? null;
                $paymentMode = $paymentDetails['paymentMode'] ?? 'PHONEPE';

                // Create payment record
                OrderPayment::create([
                    'order_id' => $order->id,
                    'payment_method' => 'phonepe',
                    'transaction_id' => $transactionId,
                    'amount' => $payload['amount'] / 100, // PhonePe uses paise
                    'currency' => 'INR',
                    'status' => 'success',
                    'gateway_response' => $payload,
                    'paid_at' => now(),
                ]);

                // Mark session as completed
                $session->markCompleted($transactionId);

                Log::info('Order created from PhonePe payment', [
                    'order_id' => $order->id,
                    'session_id' => $session->id,
                    'transaction_id' => $transactionId,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to create order from PhonePe payment', [
                'session_id' => $session->id,
                'merchantOrderId' => $merchantOrderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle PhonePe failed payment
     */
    private function handlePhonePeFailure(array $payload): void
    {
        $merchantOrderId = $payload['merchantOrderId'];

        $session = $this->checkoutSession->findByGatewayOrderId($merchantOrderId);

        if ($session) {
            Log::info('PhonePe payment failed for session', [
                'session_id' => $session->id,
                'merchantOrderId' => $merchantOrderId,
                'state' => $payload['state'] ?? 'UNKNOWN',
                'errorCode' => $payload['paymentDetails'][0]['errorCode'] ?? null,
            ]);

            // Session will expire naturally, no need to mark as failed
        }
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSuccess(array $payload): void
    {
        $gatewayOrderId = $payload['order_id'];
        $gatewayPaymentId = $payload['id'];

        // Find checkout session
        $session = $this->checkoutSession->findByGatewayOrderId($gatewayOrderId);

        if (!$session) {
            Log::error('Checkout session not found for payment', [
                'gateway_order_id' => $gatewayOrderId,
                'payment_id' => $gatewayPaymentId,
            ]);
            return;
        }

        if ($session->status !== 'active') {
            Log::warning('Checkout session not active', [
                'session_id' => $session->id,
                'status' => $session->status,
            ]);
            return;
        }

        try {
            // Create order in transaction
            DB::transaction(function () use ($session, $gatewayPaymentId, $payload) {
                // Create order with snapshots
                $order = $this->orderService->createOrderFromSession($session);

                // Create payment record
                OrderPayment::create([
                    'order_id' => $order->id,
                    'payment_method' => $session->payment_method,
                    'transaction_id' => $gatewayPaymentId,
                    'amount' => $payload['amount'] / 100, // Razorpay uses paise
                    'currency' => $payload['currency'],
                    'status' => 'success',
                    'gateway_response' => $payload,
                    'paid_at' => now(),
                ]);

                // Mark session as completed
                $session->markCompleted($gatewayPaymentId);

                Log::info('Order created from payment', [
                    'order_id' => $order->id,
                    'session_id' => $session->id,
                    'payment_id' => $gatewayPaymentId,
                ]);

                // TODO: Send order confirmation email
                // TODO: Deduct inventory
                // TODO: Create sponsor/adopt records if applicable
            });
        } catch (\Exception $e) {
            Log::error('Failed to create order from payment', [
                'session_id' => $session->id,
                'payment_id' => $gatewayPaymentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailure(array $payload): void
    {
        $gatewayOrderId = $payload['order_id'];

        $session = $this->checkoutSession->findByGatewayOrderId($gatewayOrderId);

        if ($session) {
            Log::info('Payment failed for session', [
                'session_id' => $session->id,
                'gateway_order_id' => $gatewayOrderId,
                'error' => $payload['error_description'] ?? 'Unknown error',
            ]);

            // Session will expire naturally, no need to mark as failed
        }
    }

    /**
     * Verify Razorpay webhook signature
     */
    private function verifyRazorpaySignature(string $payload, ?string $signature, string $secret): bool
    {
        if (!$signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
