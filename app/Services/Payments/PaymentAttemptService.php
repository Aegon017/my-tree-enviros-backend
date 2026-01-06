<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PlanPrice;
use App\Models\ProductVariant;
use App\Models\ShippingAddress;
use App\Notifications\OrderPaidNotification;
use App\Repositories\OrderRepository;
use App\Services\Coupons\CouponService;
use App\Services\Orders\OrderPricingService;
use App\Services\Orders\OrderSnapshotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final readonly class PaymentAttemptService
{
    public function __construct(
        private OrderPricingService $pricing,
        private CouponService $coupons,
        private OrderSnapshotService $snapshot,
        private OrderRepository $orderRepository
    ) {}

    /**
     * Create a payment attempt (replaces createDraftOrder)
     */
    public function createAttempt(array $payload, int $userId): PaymentAttempt
    {
        return DB::transaction(function () use ($payload, $userId): PaymentAttempt {
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

            // Capture shipping address snapshot
            $shippingAddress = null;
            if (! empty($payload['shipping_address_id'])) {
                $shippingAddress = ShippingAddress::find($payload['shipping_address_id']);
            }
            $shippingSnapshot = $this->snapshot->createShippingAddressSnapshot($shippingAddress);

            $attempt = PaymentAttempt::create([
                'user_id' => $userId,
                'attempt_reference' => 'ATT-' . time() . '-' . random_int(1000, 9999),
                'payment_method' => $payload['payment_method'] ?? 'razorpay',
                'status' => 'initiated',
                'subtotal' => $totals['subtotal'],
                'total_discount' => $totals['discount'],
                'total_tax' => $totals['tax'],
                'total_shipping' => $totals['shipping'],
                'total_fee' => $totals['fee'],
                'grand_total' => $totals['grand_total'],
                'currency' => $payload['currency'] ?? 'INR',
                'coupon_id' => $couponId,
                'shipping_address_id' => $payload['shipping_address_id'] ?? null,
                'shipping_address_snapshot' => $shippingSnapshot,
                'expires_at' => now()->addHours(24),
            ]);

            foreach ($items as $item) {
                // Create immutable snapshot of the item
                $itemSnapshot = $this->snapshot->createItemSnapshot($item);
                $itemName = $this->snapshot->extractItemName($itemSnapshot);
                $unitPrice = $this->snapshot->extractUnitPrice($itemSnapshot);

                $attemptItem = $attempt->items()->create([
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
                    'item_snapshot' => $itemSnapshot,
                    'item_name' => $itemName,
                    'unit_price' => $unitPrice,
                    'dedication' => $item['dedication'] ?? null,
                ]);

                // Copy image from source entity to attempt item
                $this->snapshot->copySnapshotImage($item, $attemptItem);
            }

            foreach ($totals['applied_charges'] as $c) {
                $attempt->charges()->create([
                    'charge_id' => $c['charge_id'],
                    'type' => $c['type'],
                    'label' => $c['label'],
                    'amount' => $c['amount'],
                    'meta' => $c['meta'],
                ]);
            }

            return $attempt;
        });
    }

    /**
     * Convert payment attempt to order after successful payment
     */
    public function convertToOrder(PaymentAttempt $attempt): Order
    {
        return DB::transaction(function () use ($attempt): Order {
            // Create the order
            $order = $this->orderRepository->create([
                'user_id' => $attempt->user_id,
                'reference_number' => 'ORD-' . time() . '-' . random_int(1000, 9999),
                'status' => 'pending', // Will be updated to 'paid' by payment service
                'subtotal' => $attempt->subtotal,
                'total_discount' => $attempt->total_discount,
                'total_tax' => $attempt->total_tax,
                'total_shipping' => $attempt->total_shipping,
                'total_fee' => $attempt->total_fee,
                'grand_total' => $attempt->grand_total,
                'coupon_id' => $attempt->coupon_id,
                'payment_method' => $attempt->payment_method,
                'currency' => $attempt->currency,
                'shipping_address_id' => $attempt->shipping_address_id,
                'shipping_address_snapshot' => $attempt->shipping_address_snapshot,
            ]);

            // Create order items from attempt items
            foreach ($attempt->items as $attemptItem) {
                $orderItem = $this->orderRepository->createItem([
                    'order_id' => $order->id,
                    'type' => $attemptItem->type,
                    'product_variant_id' => $attemptItem->product_variant_id,
                    'campaign_id' => $attemptItem->campaign_id,
                    'tree_id' => $attemptItem->tree_id,
                    'plan_id' => $attemptItem->plan_id,
                    'plan_price_id' => $attemptItem->plan_price_id,
                    'initiative_site_id' => $attemptItem->initiative_site_id,
                    'tree_instance_id' => $attemptItem->tree_instance_id,
                    'sponsor_quantity' => $attemptItem->sponsor_quantity,
                    'quantity' => $attemptItem->quantity,
                    'amount' => $attemptItem->amount,
                    'total_amount' => $attemptItem->total_amount,
                    'item_snapshot' => $attemptItem->item_snapshot,
                    'item_name' => $attemptItem->item_name,
                    'unit_price' => $attemptItem->unit_price,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                ]);

                // Copy image from attempt item to order item
                $media = $attemptItem->getFirstMedia('snapshot_image');
                if ($media) {
                    $media->copy($orderItem, 'snapshot_image');
                }

                // Create dedication if exists
                if ($attemptItem->dedication) {
                    $orderItem->dedication()->create([
                        'name' => $attemptItem->dedication['name'] ?? '',
                        'occasion' => $attemptItem->dedication['occasion'] ?? '',
                        'message' => $attemptItem->dedication['message'] ?? null,
                    ]);
                }
            }

            // Create order charges from attempt charges
            foreach ($attempt->charges as $attemptCharge) {
                $this->orderRepository->createCharge([
                    'order_id' => $order->id,
                    'charge_id' => $attemptCharge->charge_id,
                    'type' => $attemptCharge->type,
                    'label' => $attemptCharge->label,
                    'amount' => $attemptCharge->amount,
                    'meta' => $attemptCharge->meta,
                ]);
            }

            // Attach coupon if exists
            if ($attempt->coupon_id) {
                $this->orderRepository->attachCoupon($order, $attempt->coupon_id);
            }

            // Mark attempt as completed
            $attempt->update([
                'status' => 'completed',
                'completed_at' => now(),
                'created_order_id' => $order->id,
            ]);

            return $order;
        });
    }

    /**
     * Mark attempt as failed
     */
    public function markFailed(PaymentAttempt $attempt, string $reason = 'Payment failed'): void
    {
        $attempt->update([
            'status' => 'failed',
            'metadata' => array_merge($attempt->metadata ?? [], [
                'failure_reason' => $reason,
                'failed_at' => now()->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * Clean up expired attempts (run via scheduler)
     */
    public function cleanupExpired(): int
    {
        return PaymentAttempt::where('status', 'initiated')
            ->where('expires_at', '<', now())
            ->delete();
    }
}
