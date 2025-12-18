<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\PlanPrice;
use App\Models\ProductVariant;
use App\Repositories\OrderRepository;
use App\Services\Coupons\CouponService;
use Illuminate\Support\Facades\DB;

final readonly class OrderService
{
    public function __construct(
        private OrderPricingService $pricing,
        private CouponService $coupons,
        private OrderRepository $repository
    ) {}

    public function createDraftOrder(array $payload, int $userId): Order
    {
        return DB::transaction(function () use ($payload, $userId): Order {
            $items = collect($payload['items'])->map(function (array $item): array {
                if ($item['type'] === 'product') {
                    $variant = ProductVariant::findOrFail($item['product_variant_id']);
                    $price = $variant->selling_price ?? $variant->original_price;
                } elseif ($item['type'] === 'sponsor' || $item['type'] === 'adopt') {
                    $planPrice = PlanPrice::findOrFail($item['plan_price_id']);
                    $price = $planPrice->price;
                } else {
                    $price = $item['amount'];
                }

                return array_merge($item, [
                    'amount' => $price,
                    'total_amount' => $item['quantity'] * $price,
                ]);
            })->toArray();

            $subtotal = collect($items)->sum('total_amount');

            $couponResult = $this->coupons->validateAndCalculate($payload['coupon_code'] ?? null, $subtotal);
            $couponId = $couponResult['coupon']->id ?? null;

            $totals = $this->pricing->calculateTotals($items, $couponResult);

            $order = $this->repository->create([
                'user_id' => $userId,
                'reference_number' => 'ORD-'.time().'-'.random_int(1000, 9999),
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
                'shipping_address_id' => $payload['shipping_address_id'] ?? null,
            ]);

            foreach ($items as $item) {
                $orderItem = $this->repository->createItem([
                    'order_id' => $order->id,
                    'type' => $item['type'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'campaign_id' => $item['campaign_id'] ?? null,
                    'tree_id' => $item['tree_id'] ?? null,
                    'plan_id' => $item['plan_id'] ?? null,
                    'plan_price_id' => $item['plan_price_id'] ?? null,
                    'initiative_site_id' => $item['initiative_site_id'] ?? null,
                    'tree_instance_id' => $item['tree_instance_id'] ?? null,
                    'sponsor_quantity' => $item['sponsor_quantity'] ?? null,
                    'quantity' => $item['quantity'],
                    'amount' => $item['amount'],
                    'total_amount' => $item['total_amount'],
                ]);

                // Create dedication if provided
                if (! empty($item['dedication'])) {
                    $orderItem->dedication()->create([
                        'name' => $item['dedication']['name'] ?? '',
                        'occasion' => $item['dedication']['occasion'] ?? '',
                        'message' => $item['dedication']['message'] ?? null,
                    ]);
                }
            }

            foreach ($totals['applied_charges'] as $c) {
                $this->repository->createCharge([
                    'order_id' => $order->id,
                    'charge_id' => $c['charge_id'],
                    'type' => $c['type'],
                    'label' => $c['label'],
                    'amount' => $c['amount'],
                    'meta' => $c['meta'],
                ]);
            }

            if ($couponId) {
                $this->repository->attachCoupon($order, $couponId);
            }

            return $order;
        });
    }

    public function recordPayment(Order $order, array $paymentData): void
    {
        $this->repository->createPayment([
            'order_id' => $order->id,
            'amount' => $order->grand_total,
            'payment_method' => $paymentData['method'],
            'transaction_id' => $paymentData['transaction_id'] ?? null,
            'status' => $paymentData['status'] ?? 'pending',
            'paid_at' => ($paymentData['status'] ?? null) === 'paid' ? now() : null,
        ]);

        if (($paymentData['status'] ?? null) === 'paid') {
            $this->repository->update($order, ['status' => 'paid', 'paid_at' => now()]);
        }
    }
}
