<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TreeStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Traits\ResponseHelpers;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

/**
 * @OA\Tag(
 *     name="Payment",
 *     description="Payment processing and verification endpoints (Razorpay integration)"
 * )
 */
final class PaymentController extends Controller
{
    use ResponseHelpers;

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{orderId}/payment/initiate",
     *     summary="Initiate payment",
     *     description="Initiate Razorpay payment for an order",
     *     tags={"Payment"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"payment_method"},
     *
     *             @OA\Property(property="payment_method", type="string", enum={"razorpay"}, example="razorpay", description="Payment method")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment initiated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment initiated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="razorpay_order_id", type="string", example="order_MYtreexyz123", description="Razorpay order ID"),
     *                 @OA\Property(property="amount", type="number", format="float", example=590.00, description="Order amount"),
     *                 @OA\Property(property="currency", type="string", example="INR", description="Currency code"),
     *                 @OA\Property(property="order_number", type="string", example="ORD-ABC-20250101-1234", description="Order number"),
     *                 @OA\Property(property="key", type="string", example="rzp_test_xxxxxxxxxx", description="Razorpay API key for frontend")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Order cannot be paid (already paid/cancelled)"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to initiate payment"
     *     )
     * )
     */
    public function initiateRazorpay(Request $request, string $orderId): JsonResponse
    {
        $request->validate([
            'payment_method' => 'required|string|in:razorpay',
        ]);

        $order = Order::with('items.treeInstance')
            ->where('user_id', $request->user()->id)
            ->find($orderId);

        if (! $order) {
            return $this->notFound('Order not found');
        }

        if ($order->status !== OrderStatusEnum::PENDING) {
            return $this->error('Order cannot be paid. Current status: '.$order->status->label(), 422);
        }

        try {
            // Initialize Razorpay API
            $api = new Api(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );

            // Create Razorpay order
            $amount = (int) round($order->total_amount * 100); // Convert to paise and ensure no decimal values
            $razorpayOrder = $api->order->create([
                'receipt' => $order->reference_number,
                'amount' => $amount, // Amount in paise
                'currency' => $order->currency,
                'notes' => [
                    'order_id' => $order->id,
                    'order_number' => $order->reference_number,
                    'user_id' => $order->user_id,
                    'original_amount' => $order->total_amount,
                ],
            ]);

            // Store payment information
            OrderPayment::create([
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'payment_method' => PaymentMethodEnum::RAZORPAY->value,
                'transaction_id' => $razorpayOrder->id,
                'status' => 'initiated',
                'paid_at' => null,
            ]);

            return $this->success([
                'razorpay_order_id' => $razorpayOrder->id,
                // Return amount in paise for the frontend/checkout (amount expected in smallest currency unit)
                'amount' => $amount,
                'amount_rupees' => $order->total_amount,
                'currency' => $order->currency,
                    'order_number' => $order->reference_number,
                'key' => config('services.razorpay.key'),
            ], 'Payment initiated successfully');
        } catch (Exception $exception) {
            Log::error('Razorpay payment initiation failed', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);

            return $this->error('Failed to initiate payment: '.$exception->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{orderId}/payment/verify",
     *     summary="Verify payment",
     *     description="Verify Razorpay payment signature and update order status",
     *     tags={"Payment"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"razorpay_order_id", "razorpay_payment_id", "razorpay_signature"},
     *
     *             @OA\Property(property="razorpay_order_id", type="string", example="order_MYtreexyz123", description="Razorpay order ID"),
     *             @OA\Property(property="razorpay_payment_id", type="string", example="pay_MYtreeabc456", description="Razorpay payment ID"),
     *             @OA\Property(property="razorpay_signature", type="string", example="signature_hash_here", description="Razorpay signature")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment verified successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment verified successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order", ref="#/components/schemas/Order"),
     *                 @OA\Property(property="payment_id", type="string", example="pay_MYtreeabc456")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Payment verification failed. Invalid signature"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Payment verification failed"
     *     )
     * )
     */
    public function verifyRazorpay(Request $request, string $orderId): JsonResponse
    {
        $validated = $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        return DB::transaction(function () use ($request, $orderId, $validated): JsonResponse {
            $order = Order::with('items.treeInstance')
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->find($orderId);

            if (! $order) {
                return $this->notFound('Order not found');
            }

            if ($order->status !== OrderStatusEnum::PENDING) {
                return $this->error('Order payment already processed', 422);
            }

            try {
                // Initialize Razorpay API
                $api = new Api(
                    config('services.razorpay.key'),
                    config('services.razorpay.secret')
                );

                // Verify payment signature
                $attributes = [
                    'razorpay_order_id' => $validated['razorpay_order_id'],
                    'razorpay_payment_id' => $validated['razorpay_payment_id'],
                    'razorpay_signature' => $validated['razorpay_signature'],
                ];

                $api->utility->verifyPaymentSignature($attributes);

                // Fetch payment details
                $payment = $api->payment->fetch($validated['razorpay_payment_id']);

                // Update order status
                $order->status = OrderStatusEnum::PAID;
                $order->save();

                // Update payment record
                $orderPayment = OrderPayment::where('order_id', $order->id)
                    ->where('transaction_id', $validated['razorpay_order_id'])
                    ->first();

                // Get payment amount from Razorpay payment details and convert from paise to rupees
                $paidAmount = $payment->amount / 100;

                if ($orderPayment) {
                    $orderPayment->update([
                        'transaction_id' => $validated['razorpay_payment_id'],
                        'status' => 'success',
                        'paid_at' => now(),
                        'amount' => $paidAmount, // Update with actual paid amount
                    ]);
                } else {
                    OrderPayment::create([
                        'order_id' => $order->id,
                        'amount' => $paidAmount,
                        'payment_method' => PaymentMethodEnum::RAZORPAY->value,
                        'transaction_id' => $validated['razorpay_payment_id'],
                        'status' => 'success',
                        'paid_at' => now(),
                    ]);
                }

                // Update tree instance statuses
                foreach ($order->items as $item) {
                    $treeInstance = $item->treeInstance;
                    $planType = $item->treePlanPrice->plan->type->value;

                    if ($planType === 'sponsorship') {
                        $treeInstance->status = TreeStatusEnum::SPONSORED;
                    } elseif ($planType === 'adoption') {
                        $treeInstance->status = TreeStatusEnum::ADOPTED;
                    }

                    $treeInstance->save();

                    // Log status change
                    \App\Models\TreeStatusLog::create([
                        'tree_instance_id' => $treeInstance->id,
                        'status' => $treeInstance->status->value,
                        'user_id' => $request->user()->id,
                        'notes' => sprintf('Status changed to %s after successful payment for order %s', $treeInstance->status->label(), $order->reference_number),
                    ]);

                    // Create renewal schedule if needed
                    if ($planType === 'sponsorship') {
                        $reminderDate = \Carbon\Carbon::parse($item->end_date)->subDays(30);

                        \App\Models\TreeRenewalSchedule::create([
                            'order_item_id' => $item->id,
                            'reminder_date' => $reminderDate,
                            'reminder_sent' => false,
                        ]);
                    }
                }

                Log::info('Payment verified successfully', [
                    'order_id' => $order->id,
                    'payment_id' => $validated['razorpay_payment_id'],
                ]);

                return $this->success([
                    'order' => new \App\Http\Resources\Api\V1\OrderResource($order->load(['items.treeInstance.tree', 'items.treePlanPrice.plan'])),
                    'payment_id' => $validated['razorpay_payment_id'],
                ], 'Payment verified successfully');
            } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
                Log::error('Razorpay signature verification failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                // Update payment status to failed
                OrderPayment::where('order_id', $order->id)
                    ->where('transaction_id', $validated['razorpay_order_id'])
                    ->update(['status' => 'failed']);

                $order->status = OrderStatusEnum::FAILED;
                $order->save();

                return $this->error('Payment verification failed. Invalid signature.', 422);
            } catch (Exception $e) {
                Log::error('Payment verification failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $order->status = OrderStatusEnum::FAILED;
                $order->save();

                return $this->error('Payment verification failed: '.$e->getMessage(), 500);
            }
        });
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payments/webhook/razorpay",
     *     summary="Razorpay webhook",
     *     description="Webhook endpoint for Razorpay payment notifications (called by Razorpay servers)",
     *     tags={"Payment"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Webhook payload from Razorpay",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="event", type="string", example="payment.captured", description="Webhook event type"),
     *             @OA\Property(
     *                 property="payload",
     *                 type="object",
     *                 description="Event payload data"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Webhook processed successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Webhook processed")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Invalid webhook signature"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Webhook processing failed"
     *     )
     * )
     */
    public function webhook(Request $request): JsonResponse
    {
        $webhookSecret = config('services.razorpay.webhook_secret');
        $webhookSignature = $request->header('X-Razorpay-Signature');
        $webhookBody = $request->getContent();

        try {
            // Verify webhook signature
            $expectedSignature = hash_hmac('sha256', $webhookBody, (string) $webhookSecret);

            if ($webhookSignature !== $expectedSignature) {
                Log::warning('Invalid webhook signature');

                return $this->error('Invalid signature', 401);
            }

            $payload = $request->all();
            $event = $payload['event'] ?? null;

            Log::info('Razorpay webhook received', [
                'event' => $event,
                'payload' => $payload,
            ]);

            // Handle different webhook events
            match ($event) {
                'payment.authorized', 'payment.captured' => $this->handlePaymentSuccess($payload),
                'payment.failed' => $this->handlePaymentFailed($payload),
                'order.paid' => $this->handleOrderPaid($payload),
                default => Log::info('Unhandled webhook event', ['event' => $event]),
            };

            return $this->success(null, 'Webhook processed');
        } catch (Exception $exception) {
            Log::error('Webhook processing failed', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->error('Webhook processing failed', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{orderId}/payment/status",
     *     summary="Get payment status",
     *     description="Get the payment status for a specific order",
     *     tags={"Payment"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order_id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-ABC-20250101-1234"),
     *                 @OA\Property(property="order_status", type="string", example="paid"),
     *                 @OA\Property(property="order_status_label", type="string", example="Paid"),
     *                 @OA\Property(
     *                     property="payment",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="amount", type="string", example="590.00"),
     *                     @OA\Property(property="payment_method", type="string", example="razorpay"),
     *                     @OA\Property(property="transaction_id", type="string", example="pay_MYtreeabc456"),
     *                     @OA\Property(property="status", type="string", example="success"),
     *                     @OA\Property(property="paid_at", type="string", format="date-time", example="2025-01-01T12:00:00.000000Z")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     */
    public function status(Request $request, string $orderId): JsonResponse
    {
        $order = Order::with('items.treeInstance')
            ->where('user_id', $request->user()->id)
            ->find($orderId);

        if (! $order) {
            return $this->notFound('Order not found');
        }

        $payment = OrderPayment::where('order_id', $order->id)
            ->latest()
            ->first();

        return $this->success([
            'order_id' => $order->id,
                'order_number' => $order->reference_number,
            'order_status' => $order->status->value,
            'order_status_label' => $order->status->label(),
            'payment' => $payment ? [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'transaction_id' => $payment->transaction_id,
                'status' => $payment->status,
                'paid_at' => $payment->paid_at,
            ] : null,
        ]);
    }

    /**
     * Handle successful payment webhook
     */
    private function handlePaymentSuccess(array $payload): void
    {
        DB::transaction(function () use ($payload): void {
            $paymentId = $payload['payload']['payment']['entity']['id'] ?? null;
            $orderId = $payload['payload']['payment']['entity']['order_id'] ?? null;

            if (! $paymentId || ! $orderId) {
                return;
            }

            $orderPayment = OrderPayment::where('transaction_id', $orderId)->first();

            if ($orderPayment) {
                // Get payment amount from webhook payload and convert from paise to rupees
                $paidAmount = ($payload['payload']['payment']['entity']['amount'] ?? 0) / 100;

                $orderPayment->update([
                    'transaction_id' => $paymentId,
                    'status' => 'success',
                    'paid_at' => now(),
                    'amount' => $paidAmount,
                ]);

                $order = $orderPayment->order;
                if ($order && $order->status === OrderStatusEnum::PENDING) {
                    $order->status = OrderStatusEnum::PAID;
                    $order->save();

                    // Update tree statuses
                    foreach ($order->items as $item) {
                        $treeInstance = $item->treeInstance;
                        $planType = $item->treePlanPrice->plan->type->value;

                        if ($planType === 'sponsorship') {
                            $treeInstance->status = TreeStatusEnum::SPONSORED;
                        } elseif ($planType === 'adoption') {
                            $treeInstance->status = TreeStatusEnum::ADOPTED;
                        }

                        $treeInstance->save();
                    }
                }
            }
        });
    }

    /**
     * Handle failed payment webhook
     */
    private function handlePaymentFailed(array $payload): void
    {
        DB::transaction(function () use ($payload): void {
            $orderId = $payload['payload']['payment']['entity']['order_id'] ?? null;

            if (! $orderId) {
                return;
            }

            $orderPayment = OrderPayment::where('transaction_id', $orderId)->first();

            if ($orderPayment) {
                $orderPayment->update(['status' => 'failed']);

                $order = $orderPayment->order;
                if ($order && $order->status === OrderStatusEnum::PENDING) {
                    $order->status = OrderStatusEnum::FAILED;
                    $order->save();
                }
            }
        });
    }

    /**
     * Handle order paid webhook
     */
    private function handleOrderPaid(array $payload): void
    {
        DB::transaction(function () use ($payload): void {
            $orderId = $payload['payload']['order']['entity']['id'] ?? null;

            if (! $orderId) {
                return;
            }

            $orderPayment = OrderPayment::where('transaction_id', $orderId)->first();

            if ($orderPayment) {
                $order = $orderPayment->order;
                if ($order && $order->status === OrderStatusEnum::PENDING) {
                    $order->status = OrderStatusEnum::PAID;
                    $order->save();
                }
            }
        });
    }
}
