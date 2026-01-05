<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\OrderPayment;
use Razorpay\Api\Api;

final readonly class RazorpayService
{
    private Api $api;

    public function __construct()
    {
        $this->api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }

    public function createGatewayOrder(\App\Models\CheckoutSession $session): array
    {
        $rzpOrder = $this->api->order->create([
            'receipt' => 'SESSION-' . $session->id,
            'amount' => (int) round($session->pricing['grand_total'] * 100),
            'currency' => $session->currency ?? 'INR',
        ]);

        return [
            'gateway' => 'razorpay',
            'order_id' => $rzpOrder['id'],
            'amount' => (int) round($session->pricing['grand_total'] * 100),
            'currency' => $session->currency ?? 'INR',
            'key' => config('services.razorpay.key'),
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

        $order = Order::where('reference_number', $payload['order_reference'])->firstOrFail();

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

        return [
            'success' => true,
            'order_id' => $order->id,
            'reference_number' => $order->reference_number,
        ];
    }
}
