<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderCharge;
use App\Models\OrderItem;
use App\Services\Coupons\CouponService;
use Illuminate\Support\Facades\DB;

final readonly class OrderService
{
    public function __construct(
        private OrderPricingService $pricing,
        private CouponService $coupons
    ) {}

    public function createDraftOrder(array $payload, int $userId): Order
    {
        return DB::transaction(function () use ($payload, $userId) {
            $items = $payload['items'];

            $subtotal = collect($items)->sum(fn($item): int|float => $item['quantity'] * $item['amount']);

            $couponResult = $this->coupons->validateAndCalculate($payload['coupon_code'] ?? null, $subtotal);
            $couponId = $couponResult['coupon']->id ?? null;

            $totals = $this->pricing->calculateTotals($items, $couponResult);

            $order = Order::create([
                'user_id' => $userId,
                'reference_number' => 'ORD-' . time() . '-' . random_int(1000, 9999),
                'status' => 'pending',
                'subtotal' => $totals['subtotal'],
                'total_discount' => $totals['discount'],
                'total_tax' => $totals['tax'],
                'total_shipping' => $totals['shipping'],
                'total_fee' => $totals['fee'],
                'grand_total' => $totals['grand_total'],
                'coupon_id' => $couponId,
                'payment_method' => $payload['payment_method'] ?? null,
                'currency' => $payload['currency'] ?? 'INR',
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'type' => $item['type'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'campaign_id' => $item['campaign_id'] ?? null,
                    'tree_id' => $item['tree_id'] ?? null,
                    'plan_id' => $item['plan_id'] ?? null,
                    'plan_price_id' => $item['plan_price_id'] ?? null,
                    'tree_instance_id' => $item['tree_instance_id'] ?? null,
                    'sponsor_quantity' => $item['sponsor_quantity'] ?? null,
                    'quantity' => $item['quantity'],
                    'amount' => $item['amount'],
                    'total_amount' => $item['quantity'] * $item['amount'],
                ]);
            }

            foreach ($totals['applied_charges'] as $c) {
                OrderCharge::create([
                    'order_id' => $order->id,
                    'charge_id' => $c['charge_id'],
                    'type' => $c['type'],
                    'label' => $c['label'],
                    'amount' => $c['amount'],
                    'meta' => $c['meta'],
                ]);
            }

            if ($couponId) {
                $this->coupons->markRedeemed($couponId, $order->id);
            }

            return $order;
        });
    }
}
