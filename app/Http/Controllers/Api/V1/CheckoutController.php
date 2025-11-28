<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckoutRequest;
use App\Services\Orders\OrderService;
use App\Services\Payments\PaymentFactory;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly OrderService $orders
    ) {}

    public function prepare(CheckoutRequest $request)
    {
        $userId = $request->user()->id;

        $payload = [
            'items' => $request->items,
            'coupon_code' => $request->coupon_code,
            'payment_method' => 'razorpay',
            'currency' => 'INR',
        ];

        $order = $this->orders->createDraftOrder($payload, $userId);

        $payment = PaymentFactory::driver('razorpay')->createGatewayOrder($order);

        return response()->json([
            'order' => [
                'id' => $order->id,
                'reference_number' => $order->reference_number,
                'grand_total' => $order->grand_total,
            ],
            'payment' => $payment,
        ]);
    }
}
