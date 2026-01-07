<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentAttempt;
use App\Models\User;
use App\Notifications\OrderPaidNotification;
use Illuminate\Support\Facades\Notification;
use Razorpay\Api\Api;

final readonly class RazorpayService
{
    private Api $api;

    public function __construct()
    {
        $this->api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }

    public function createGatewayOrder(PaymentAttempt $attempt): array
    {
        $rzpOrder = $this->api->order->create([
            'receipt' => $attempt->attempt_reference,
            'amount' => (int) round($attempt->grand_total * 100),
            'currency' => $attempt->currency ?? 'INR',
        ]);

        // Store gateway order ID in attempt
        $attempt->update(['payment_gateway_order_id' => $rzpOrder['id']]);

        return [
            'gateway' => 'razorpay',
            'key' => config('services.razorpay.key'),
            'order_id' => $rzpOrder['id'],
            'amount' => (int) round($attempt->grand_total * 100),
            'currency' => $attempt->currency ?? 'INR',
        ];
    }

    public function verifyAndCapture(array $payload): array
    {
        $attributes = [
            'razorpay_order_id' => $payload['razorpay_order_id'],
            'razorpay_payment_id' => $payload['razorpay_payment_id'],
            'razorpay_signature' => $payload['razorpay_signature'],
        ];

        $this->api->utility->verifyPaymentSignature($attributes);

        $attempt = PaymentAttempt::where('attempt_reference', $payload['attempt_reference'])->firstOrFail();

        // Convert attempt to order
        $order = app(PaymentAttemptService::class)->convertToOrder($attempt);

        OrderPayment::create([
            'order_id' => $order->id,
            'amount' => $order->grand_total,
            'payment_method' => 'razorpay',
            'transaction_id' => $payload['razorpay_payment_id'],
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
