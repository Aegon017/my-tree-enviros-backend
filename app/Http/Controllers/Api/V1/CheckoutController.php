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
use App\Http\Resources\Api\V1\OrderResource;
use App\Services\Payments\PaymentAttemptService;
use App\Services\Payments\PaymentFactory;
use Illuminate\Http\Request;

final class CheckoutController extends Controller
{
    use \App\Traits\ResponseHelpers;

    public function __construct(
        private readonly OrderService $orders,
        private readonly CouponService $coupons,
        private readonly CheckoutService $checkout,
        private readonly PaymentAttemptService $paymentAttempts
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        if ($request->mode === 'buy_now') {
            return $this->buildBuyNowCheckout($request);
        }

        $cart = $user->cart()->with('items')->first();

        if (! $cart || $cart->items->isEmpty()) {
            return $this->error('Cart is empty', 422);
        }

        $items = $cart->items;
        $summary = $this->checkout->buildSummary($items, $request->query('coupon_code'));

        return $this->success($summary);
    }

    public function prepare(CheckoutRequest $request)
    {
        $userId = $request->user()->id;
        $paymentMethod = $request->payment_method ?? 'razorpay';

        // COD Exception: Create order immediately
        if ($paymentMethod === 'cod') {
            return $this->handleCODCheckout($request, $userId);
        }

        // Online payment: Create checkout session (NOT order)
        $session = $this->checkoutSession->createSession([
            'items' => $request->items,
            'coupon_code' => $request->coupon_code,
            'payment_method' => $paymentMethod,
            'currency' => 'INR',
            'shipping_address_id' => $request->shipping_address_id,
        ], $userId);

        // Create payment gateway order
        $driver = $paymentMethod;
        $payment = PaymentFactory::driver($driver)->createGatewayOrder($session);

        // Store gateway order ID in session
        $session->update([
            'gateway_order_id' => $payment['order_id'] ?? $payment['id'] ?? null,
            'gateway_response' => $payment,
        ]);

        return $this->success([
            'session_token' => $session->session_token,
            'payment' => $payment,
            'expires_at' => $session->expires_at,
            'pricing' => $session->pricing,
        ]);
    }

    /**
     * Handle COD checkout (exception to payment-first rule)
     */
    private function handleCODCheckout(CheckoutRequest $request, int $userId)
    {
        $payload = [
            'items' => $request->items,
            'coupon_code' => $request->coupon_code,
            'payment_method' => 'cod',
            'currency' => 'INR',
            'shipping_address_id' => $request->shipping_address_id,
        ];

        // Create payment attempt instead of draft order
        $attempt = $this->paymentAttempts->createAttempt($payload, $userId);

        $attempt->load('charges');

        $driver = $request->payment_method ?? 'razorpay';
        $payment = PaymentFactory::driver($driver)->createGatewayOrder($attempt);

        return $this->success([
            'attempt' => [
                'id' => $attempt->id,
                'attempt_reference' => $attempt->attempt_reference,
                'grand_total' => $attempt->grand_total,
                'currency' => $attempt->currency,
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
            return $this->error('Invalid coupon', 422);
        }

        return $this->success([
            'discount' => $result['discount'],
            'coupon' => $result['coupon'],
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
                'name' => $variant->product?->name ?? 'Unknown Product',
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
                'duration' => $planPrice->plan?->duration ?? 0,
                'duration_unit' => $planPrice->plan?->duration_unit ?? 'months',
                'image_url' => $planPrice->tree?->image_url ?? '',
                'tree' => [
                    'id' => $planPrice->tree?->id,
                    'name' => $planPrice->tree?->name ?? 'Unknown Tree',
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

        return $this->success($summary);
    }
}
