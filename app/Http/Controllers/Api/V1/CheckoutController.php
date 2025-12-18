<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckoutRequest;
use App\Models\Campaign;
use App\Models\PlanPrice;
use App\Models\ProductVariant;
use App\Services\CheckoutService;
use App\Services\Coupons\CouponService;
use App\Services\Orders\OrderService;
use App\Services\Payments\PaymentFactory;
use Illuminate\Http\Request;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly CouponService $coupons,
        private readonly CheckoutService $checkout
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        if ($request->mode === 'buy_now') {
            return $this->buildBuyNowCheckout($request);
        }

        $cart = $user->cart()->with('items')->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty',
            ], 422);
        }

        $items = $cart->items;
        $summary = $this->checkout->buildSummary($items, $request->query('coupon_code'));

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    public function prepare(CheckoutRequest $request)
    {
        $userId = $request->user()->id;

        $payload = [
            'items' => $request->items,
            'coupon_code' => $request->coupon_code,
            'payment_method' => $request->payment_method ?? 'razorpay',
            'currency' => 'INR',
            'shipping_address_id' => $request->shipping_address_id,
        ];

        $order = $this->orders->createDraftOrder($payload, $userId);

        $order->load('orderCharges');

        $driver = $request->payment_method ?? 'razorpay';
        $payment = PaymentFactory::driver($driver)->createGatewayOrder($order);

        return response()->json([
            'order' => [
                'id' => $order->id,
                'reference_number' => $order->reference_number,
                'subtotal' => $order->subtotal,
                'discount' => $order->total_discount,
                'tax' => $order->total_tax,
                'shipping' => $order->total_shipping,
                'fee' => $order->total_fee,
                'grand_total' => $order->grand_total,
                'charges' => $order->charges ? $order->charges->map(fn ($c): array => [
                    'type' => $c->type,
                    'label' => $c->label,
                    'amount' => $c->amount,
                ]) : [],
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

        if (! $result) {
            return response()->json(['message' => 'Invalid coupon'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'discount' => $result['discount'],
                'coupon' => $result['coupon'],
            ],
        ]);
    }

    private function buildBuyNowCheckout(Request $request)
    {
        $type = $request->input('type');
        $items = collect([]);

        if ($type === 'product') {
            $variant = ProductVariant::with('product')->findOrFail($request->variant_id);

            $items = collect([[
                'type' => 'product',
                'name' => $variant->product->name,
                'quantity' => $request->quantity ?? 1,
                'price' => $variant->selling_price ?? $variant->original_price,
                'image_url' => $variant->image_url,
                'variant' => [
                    'color' => $variant->color,
                    'size' => $variant->size,
                    'planter' => $variant->planter,
                ],
                'product_variant_id' => $variant->id,
            ]]);
        }

        if ($type === 'sponsor' || $type === 'adopt') {
            $planPrice = PlanPrice::with('plan', 'tree')->findOrFail($request->plan_price_id);

            $dedication = null;
            if ($request->has('dedication_name') || $request->has('dedication_occasion') || $request->has('dedication_message')) {
                $dedication = [
                    'name' => $request->input('dedication_name'),
                    'occasion' => $request->input('dedication_occasion'),
                    'message' => $request->input('dedication_message'),
                ];
            }

            $items = collect([[
                'type' => $type,
                'quantity' => $request->quantity ?? 1,
                'price' => $planPrice->price,
                'duration' => $planPrice->plan->duration,
                'duration_unit' => $planPrice->plan->duration_unit,
                'image_url' => $planPrice->tree->image_url,
                'tree' => [
                    'id' => $planPrice->tree->id,
                    'name' => $planPrice->tree->name,
                ],
                'plan_price_id' => $planPrice->id,
                'initiative_site_id' => $request->input('initiative_site_id'),
                'tree_instance_id' => $request->input('tree_instance_id'),
                'dedication' => $dedication,
            ]]);
        }

        if ($type === 'campaign') {
            $campaign = Campaign::findOrFail($request->campaign_id);

            $items = collect([[
                'type' => 'campaign',
                'quantity' => 1,
                'amount' => (float) $request->amount,
                'name' => $campaign->name,
                'image_url' => $campaign->getFirstMedia('thumbnails')?->getFullUrl() ?? $campaign->getFirstMedia('images')?->getFullUrl(),
                'campaign_id' => $campaign->id,
            ]]);
        }

        $summary = $this->checkout->buildSummary($items, $request->query('coupon_code'));

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}
