<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanPrice;
use App\Models\ProductVariant;
use App\Services\Coupons\CouponService;
use App\Services\Orders\OrderPricingService;
use Illuminate\Support\Collection;

final readonly class CheckoutService
{
    public function __construct(
        private OrderPricingService $pricingService,
        private CouponService $couponService
    ) {}

    public function buildSummary(Collection $items, ?string $couponCode = null): array
    {
        $hydrated = $this->hydrateItems($items);

        $pricingItems = $hydrated->map(fn($i): array => [
            'quantity' => $i['quantity'],
            'amount' => $i['amount'],
        ])->toArray();

        $subtotal = collect($pricingItems)->sum(fn($i): int|float => $i['quantity'] * $i['amount']);

        $couponResult = $this->couponService->validateAndCalculate($couponCode, $subtotal);

        $totals = $this->pricingService->calculateTotals($pricingItems, $couponResult);

        $chargesMeta = collect($totals['applied_charges'])->map(fn($c): array => [
            'code' => $c['label'],
            'label' => $c['label'],
            'type' => $c['type'],
            'mode' => $c['meta']['mode'] ?? 'fixed',
            'amount' => $c['amount'],
        ])->toArray();

        return [
            'items' => $hydrated,
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'tax_total' => $totals['tax'],
            'fee_total' => $totals['fee'],
            'grand_total' => $totals['grand_total'],
            'charges' => $chargesMeta,
            'coupon' => $couponResult['coupon'] ?? null,
        ];
    }

    private function hydrateItems(Collection $items): Collection
    {
        return $items->map(function ($item): ?array {
            if ($item['type'] === 'product') {
                $variant = ProductVariant::with('inventory.product')->find($item['product_variant_id']);

                return [
                    'id' => $item['id'] ?? null,
                    'type' => 'product',
                    'quantity' => $item['quantity'],
                    'amount' => (float) ($variant->selling_price ?? $variant->original_price),
                    'total_amount' => (float) ($variant->selling_price ?? $variant->original_price) * $item['quantity'],
                    'product_variant_id' => $item['product_variant_id'],
                    'name' => $variant->inventory->product->name,
                    'image_url' => $variant->getFirstMedia('images')->getFullUrl(),
                    'variant' => [
                        'color' => $variant->color,
                        'size' => $variant->size,
                        'planter' => $variant->planter,
                    ],
                ];
            }

            if ($item['type'] === 'sponsor' || $item['type'] === 'adopt') {
                $planPrice = PlanPrice::with('plan', 'tree')->find($item['plan_price_id']);

                return [
                    'id' => $item['id'] ?? null,
                    'type' => $item['type'],
                    'quantity' => $item['quantity'],
                    'amount' => (float) $planPrice->price,
                    'total_amount' => (float) $planPrice->price * $item['quantity'],
                    'plan_price_id' => $item['plan_price_id'],
                    'plan_id' => $planPrice->plan_id,
                    'tree_id' => $planPrice->tree_id,
                    'name' => $planPrice->tree->name,
                    'image_url' => $planPrice->tree->getFirstMedia('images')->getFullUrl(),
                    'duration' => $planPrice->plan->duration,
                    'duration_unit' => $planPrice->plan->duration_unit,
                    'initiative_site_id' => $item['initiative_site_id'] ?? null,
                    'initiative_site_label' => ($item['initiative_site_id'] ?? null)
                        ? \App\Models\InitiativeSite::find($item['initiative_site_id'])?->label
                        : null,
                    'dedication' => $item['dedication'] ?? null,
                ];
            }

            if ($item['type'] === 'campaign') {
                return [
                    'id' => $item['id'] ?? null,
                    'type' => 'campaign',
                    'quantity' => $item['quantity'],
                    'amount' => (float) $item['amount'],
                    'total_amount' => (float) $item['amount'] * $item['quantity'],
                    'campaign_id' => $item['campaign_id'],
                    'name' => $item['name'],
                    'image_url' => $item['image_url'],
                ];
            }

            return null;
        })->filter()->values();
    }
}
