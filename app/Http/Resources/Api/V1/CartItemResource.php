<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->type === 'product') {
            return $this->formatProduct();
        }

        return $this->formatTreeItem();
    }

    private function formatProduct(): array
    {
        $variant = $this->productVariant;
        $product = $variant->inventory->product;

        $firstMedia = $variant->getFirstMedia('images');
        $imageUrl = $firstMedia ? $firstMedia->getFullUrl() : null;

        return [
            'id' => $this->id,
            'type' => 'product',
            'product_variant_id' => $variant->id,
            'quantity' => $this->quantity,
            'price' => (float) $this->amount,

            'name' => $product->name,
            'image_url' => $imageUrl,

            'variant' => [
                'sku' => $variant->sku,
                'color' => $variant->variant?->color?->name,
                'size' => $variant->variant?->size?->name,
                'planter' => $variant->variant?->planter?->name,
            ],
        ];
    }

    private function formatTreeItem(): array
    {
        $planPrice = $this->planPrice;
        $tree = $this->tree;
        $ded = $this->dedication;

        $firstMedia = $tree->getFirstMedia('images');
        $imageUrl = $firstMedia ? $firstMedia->getFullUrl() : null;

        $availablePlans = $tree->planPrices
            ->groupBy('plan_id')
            ->map(function ($planPrices) use ($planPrice): array {
                $firstPlanPrice = $planPrices->first();

                $filteredPlanPrices = $planPrices->filter(fn ($pp): bool => $pp->plan->type === $planPrice->plan->type);

                if ($filteredPlanPrices->isEmpty()) {
                    return [];
                }

                return [
                    'id' => $firstPlanPrice->plan->id,
                    'duration' => $firstPlanPrice->plan->duration,
                    'duration_unit' => $firstPlanPrice->plan->duration_unit,
                    'plan_prices' => $filteredPlanPrices->map(fn ($pp): array => [
                        'id' => $pp->id,
                        'price' => (float) $pp->price,
                        'plan' => [
                            'id' => $pp->plan->id,
                            'type' => $pp->plan->type,
                            'duration' => $pp->plan->duration,
                            'duration_unit' => $pp->plan->duration_unit,
                        ],
                    ])->values()->toArray(),
                ];
            })
            ->filter(fn ($item): bool => ! empty($item))
            ->values()
            ->toArray();

        return [
            'id' => $this->id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'duration' => $planPrice->plan->duration,
            'duration_unit' => $planPrice->plan->duration_unit,
            'price' => (float) $this->amount,
            'image_url' => $imageUrl,
            'plan_price_id' => $planPrice->id,

            'tree' => [
                'id' => $tree->id,
                'name' => $tree->name,
            ],

            'plan' => [
                'id' => $planPrice->plan->id,
                'duration' => $planPrice->plan->duration,
                'duration_unit' => $planPrice->plan->duration_unit,
            ],

            'available_plans' => $availablePlans,

            'dedication' => [
                'name' => $ded->name ?? null,
                'occasion' => $ded->occasion ?? null,
                'message' => $ded->message ?? null,
            ],
        ];
    }
}
