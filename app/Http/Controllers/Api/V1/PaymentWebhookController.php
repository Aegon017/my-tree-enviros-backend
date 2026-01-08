<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OrderPayment;
use App\Models\PaymentAttempt;
use App\Services\Payments\PaymentAttemptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentAttemptService $paymentAttempts
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
     * Handle PhonePe webhook (OAuth-based API)
     */
    public function phonepe(Request $request)
    {
        // Verify webhook authorization
        $authHeader = $request->header('Authorization');
        $webhookUsername = config('services.phonepe.webhook_username');
        $webhookPassword = config('services.phonepe.webhook_password');

        if ($webhookUsername && $webhookPassword) {
            $expectedAuth = hash('sha256', $webhookUsername . ':' . $webhookPassword);

            if ($authHeader !== $expectedAuth) {
                Log::error('Invalid PhonePe webhook authorization');
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $event = $request->input('event');
        $payload = $request->input('payload');

        Log::info('PhonePe webhook received', [
            'event' => $event,
            'merchantOrderId' => $payload['merchantOrderId'] ?? null,
            'state' => $payload['state'] ?? null,
        ]);

        // Handle different event types
        if ($event === 'checkout.order.completed') {
            $this->handlePhonePeSuccess($payload);
        }

        if ($event === 'checkout.order.failed') {
            $this->handlePhonePeFailure($payload);
        }

        return response()->json(['status' => 'ok'], 200);
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

        // Extract attempt ID from merchant order ID (format: MT-{attemptId}-{random})
        $parts = explode('-', $merchantOrderId);
        if (count($parts) < 2) {
            Log::error('Invalid merchant order ID format', [
                'merchantOrderId' => $merchantOrderId,
            ]);
            return;
        }

        $attemptId = (int) $parts[1];
        $attempt = PaymentAttempt::find($attemptId);

        if (!$attempt) {
            Log::error('Payment attempt not found for PhonePe payment', [
                'merchantOrderId' => $merchantOrderId,
                'attemptId' => $attemptId,
            ]);
            return;
        }

        // Check if already converted to order
        if ($attempt->status === 'completed' && $attempt->created_order_id) {
            Log::info('Payment attempt already converted to order', [
                'attempt_id' => $attempt->id,
                'order_id' => $attempt->created_order_id,
            ]);
            return;
        }

        try {
            // Create order in transaction
            DB::transaction(function () use ($attempt, $payload) {
                // Convert attempt to order
                $order = $this->paymentAttempts->convertToOrder($attempt);

                // Extract payment details
                $paymentDetails = $payload['paymentDetails'][0] ?? [];
                $transactionId = $paymentDetails['transactionId'] ?? $payload['orderId'] ?? null;

                // Create payment record
                OrderPayment::create([
                    'order_id' => $order->id,
                    'payment_method' => 'phonepe',
                    'transaction_id' => $transactionId,
                    'amount' => $order->grand_total,
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                // Update order status
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                Log::info('Order created from PhonePe payment', [
                    'order_id' => $order->id,
                    'attempt_id' => $attempt->id,
                    'transaction_id' => $transactionId,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to create order from PhonePe payment', [
                'attempt_id' => $attempt->id,
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

        // Extract attempt ID from merchant order ID
        $parts = explode('-', $merchantOrderId);
        if (count($parts) >= 2) {
            $attemptId = (int) $parts[1];
            $attempt = PaymentAttempt::find($attemptId);

            if ($attempt) {
                Log::info('PhonePe payment failed for attempt', [
                    'attempt_id' => $attempt->id,
                    'merchantOrderId' => $merchantOrderId,
                    'state' => $payload['state'] ?? 'UNKNOWN',
                    'errorCode' => $payload['errorCode'] ?? null,
                ]);

                // Attempt will expire naturally, no need to mark as failed
            }
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
