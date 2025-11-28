<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckoutRequest;
use App\Services\Orders\OrderService;
use App\Services\Payments\PaymentFactory;

use App\Services\Coupons\CouponService;
use Illuminate\Http\Request;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly CouponService $coupons
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

    public function checkCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        $result = $this->coupons->validateAndCalculate($request->coupon_code, (float) $request->amount);

        if (!$result) {
            return response()->json(['message' => 'Invalid coupon'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'discount' => $result['discount'],
                'coupon' => $result['coupon'],
            ]
        ]);
    }
}
