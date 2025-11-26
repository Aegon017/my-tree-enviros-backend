<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TreeStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Traits\ResponseHelpers;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

final class PaymentController extends Controller
{
    use ResponseHelpers;

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

        // Handle free orders (amount <= 0)
        if ($order->total <= 0) {
            return $this->handleFreeOrder($order);
        }

        try {
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

            $amount = (int) round($order->total * 100); // Amount in paise
            $razorpayOrder = $api->order->create([
                'receipt' => $order->reference_number,
                'amount' => $amount,
                'currency' => $order->currency,
                'notes' => [
                    'order_id' => $order->id,
                    'order_number' => $order->reference_number,
                    'user_id' => $order->user_id,
                ],
            ]);

            OrderPayment::create([
                'order_id' => $order->id,
                'amount' => $order->total,
                'payment_method' => PaymentMethodEnum::RAZORPAY->value,
                'transaction_id' => $razorpayOrder->id,
                'status' => 'initiated',
            ]);

            return $this->success([
                'razorpay_order_id' => $razorpayOrder->id,
                'amount' => $amount,
                'amount_rupees' => $order->total,
                'currency' => $order->currency,
                'key' => config('services.razorpay.key'),
                'order_number' => $order->reference_number,
            ], 'Payment initiated successfully');
        } catch (Exception $exception) {
            return $this->error('Failed to initiate payment: '.$exception->getMessage(), 500);
        }
    }

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
                $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

                $attributes = [
                    'razorpay_order_id' => $validated['razorpay_order_id'],
                    'razorpay_payment_id' => $validated['razorpay_payment_id'],
                    'razorpay_signature' => $validated['razorpay_signature'],
                ];

                $api->utility->verifyPaymentSignature($attributes);

                $payment = $api->payment->fetch($validated['razorpay_payment_id']);
                $paidAmount = $payment->amount / 100;

                $this->completeOrder(
                    $order,
                    $validated['razorpay_payment_id'],
                    $paidAmount,
                    PaymentMethodEnum::RAZORPAY->value,
                    $validated['razorpay_order_id']
                );

                return $this->success([
                    'order' => new OrderResource($order->load(['items.treeInstance.tree', 'items.planPrice.plan'])),
                    'payment_id' => $validated['razorpay_payment_id'],
                ], 'Payment verified successfully');
            } catch (SignatureVerificationError) {
                $this->markPaymentFailed($order, $validated['razorpay_order_id']);

                return $this->error('Payment verification failed. Invalid signature.', 422);
            } catch (Exception $e) {
                $this->markPaymentFailed($order, $validated['razorpay_order_id']);

                return $this->error('Payment verification failed: '.$e->getMessage(), 500);
            }
        });
    }

    public function status(Request $request, string $orderId): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->find($orderId);

        if (! $order) {
            return $this->notFound('Order not found');
        }

        $payment = OrderPayment::where('order_id', $order->id)->latest()->first();

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

    public function webhook(Request $request): JsonResponse
    {
        Log::info('Razorpay webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->getContent(),
        ]);

        $webhookSecret = config('services.razorpay.webhook_secret');
        $webhookSignature = $request->header('X-Razorpay-Signature');
        $webhookBody = $request->getContent();

        if (! $webhookSecret) {
            Log::error('Razorpay webhook secret not configured');

            return $this->error('Webhook configuration error', 500);
        }

        if (! $webhookSignature) {
            Log::error('Razorpay webhook signature missing');

            return $this->error('Signature missing', 401);
        }

        try {
            $expectedSignature = hash_hmac('sha256', $webhookBody, (string) $webhookSecret);

            if ($webhookSignature !== $expectedSignature) {
                Log::error('Razorpay webhook signature mismatch', [
                    'expected' => $expectedSignature,
                    'received' => $webhookSignature,
                ]);

                return $this->error('Invalid signature', 401);
            }

            $payload = $request->all();
            $event = $payload['event'] ?? null;

            Log::info('Razorpay webhook event processing', ['event' => $event, 'payload' => $payload]);

            match ($event) {
                'payment.captured' => $this->handlePaymentSuccessWebhook($payload),
                'payment.failed' => $this->handlePaymentFailedWebhook($payload),
                default => Log::info('Razorpay webhook event not handled', ['event' => $event]),
            };

            return $this->success(null, 'Webhook processed');
        } catch (Exception $exception) {
            Log::error('Razorpay webhook processing failed', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->error('Webhook processing failed', 500);
        }
    }

    private function handleFreeOrder(Order $order): JsonResponse
    {
        try {
            DB::transaction(function () use ($order): void {
                $this->completeOrder(
                    $order,
                    'FREE-'.uniqid(),
                    0,
                    PaymentMethodEnum::MANUAL->value
                );
            });

            return $this->success([
                'is_free' => true,
                'order_id' => $order->id,
                'amount' => 0,
                'currency' => $order->currency,
                'order_number' => $order->reference_number,
            ], 'Order processed successfully (Free)');
        } catch (Exception) {
            return $this->error('Failed to process free order', 500);
        }
    }

    private function completeOrder(
        Order $order,
        string $transactionId,
        float $paidAmount,
        string $paymentMethod,
        ?string $initiationTransactionId = null
    ): void {
        $order->status = OrderStatusEnum::PAID;
        $order->payment_method = $paymentMethod;
        $order->paid_at = now();
        $order->save();

        $orderPayment = null;
        if ($initiationTransactionId) {
            $orderPayment = OrderPayment::where('order_id', $order->id)
                ->where('transaction_id', $initiationTransactionId)
                ->first();
        }

        if ($orderPayment) {
            $orderPayment->update([
                'transaction_id' => $transactionId,
                'status' => 'success',
                'paid_at' => now(),
                'amount' => $paidAmount,
            ]);
        } else {
            OrderPayment::create([
                'order_id' => $order->id,
                'amount' => $paidAmount,
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
                'status' => 'success',
                'paid_at' => now(),
            ]);
        }

        foreach ($order->items as $item) {
            if ($item->treeInstance) {
                $treeInstance = $item->treeInstance;
                $planType = $item->planPrice?->plan?->type?->value;

                if ($planType === 'sponsorship') {
                    $treeInstance->status = TreeStatusEnum::SPONSORED;
                } elseif ($planType === 'adoption') {
                    $treeInstance->status = TreeStatusEnum::ADOPTED;
                }

                $treeInstance->save();
            }
        }
    }

    private function markPaymentFailed(Order $order, string $transactionId): void
    {
        OrderPayment::where('order_id', $order->id)
            ->where('transaction_id', $transactionId)
            ->update(['status' => 'failed']);

        $order->status = OrderStatusEnum::FAILED;
        $order->save();
    }

    private function handlePaymentSuccessWebhook(array $payload): void
    {
        DB::transaction(function () use ($payload): void {
            $paymentId = $payload['payload']['payment']['entity']['id'] ?? null;
            $orderId = $payload['payload']['payment']['entity']['order_id'] ?? null;
            $amount = ($payload['payload']['payment']['entity']['amount'] ?? 0) / 100;

            if (! $paymentId || ! $orderId) {
                return;
            }

            $orderPayment = OrderPayment::where('transaction_id', $orderId)->first();
            if ($orderPayment && $orderPayment->order) {
                $this->completeOrder(
                    $orderPayment->order,
                    $paymentId,
                    $amount,
                    PaymentMethodEnum::RAZORPAY->value,
                    $orderId
                );
            }
        });
    }

    private function handlePaymentFailedWebhook(array $payload): void
    {
        $orderId = $payload['payload']['payment']['entity']['order_id'] ?? null;
        if (! $orderId) {
            return;
        }

        $orderPayment = OrderPayment::where('transaction_id', $orderId)->first();
        if ($orderPayment && $orderPayment->order) {
            $this->markPaymentFailed($orderPayment->order, $orderId);
        }
    }
}
