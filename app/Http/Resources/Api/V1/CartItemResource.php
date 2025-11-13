<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->product_variant_id) {
            return $this->formatProductVariant();
        }

        if ($this->tree_instance_id) {
            return $this->formatTreeItem();
        }

        return [];
    }

    private function formatProductVariant(): array
    {
        $variant = $this->productVariant;
        $inventory = $variant->inventory;
        $product = $inventory->product;

        $image = $variant->getFirstMediaUrl('images')
            ?: $product->productCategory?->getFirstMediaUrl('images')
            ?: null;

        return [
            'id' => $this->id,
            'item_type' => 'product',
            'quantity' => $this->quantity,
            'price' => (float) ($variant->selling_price ?? $variant->original_price),

            'image' => $image,
            'productName' => $product->name,

            'variantInfo' => [
                'sku' => $variant->sku,
                'name' => trim(
                    ($variant->variant->color->name ?? '') . ' ' .
                        ($variant->variant->size->name ?? '') . ' ' .
                        ($variant->variant->planter->name ?? '')
                ),
                'color' => $variant->variant->color->name ?? null,
                'size' => $variant->variant->size->name ?? null,
                'planter' => $variant->variant->planter->name ?? null,
            ],

            'stockInfo' => [
                'quantity' => $variant->stock_quantity,
                'isInStock' => (bool)$variant->is_instock,
            ],

            'item' => [
                'product' => new ProductResource($product),
                'variant' => new ProductVariantResource($variant),
            ],
        ];
    }

    private function formatTreeItem(): array
    {
        $treeInstance = $this->treeInstance;
        $plan = $this->treePlanPrice;
        $tree = $treeInstance->tree;

        return [
            'id' => $this->id,
            'item_type' => 'tree',
            'quantity' => 1,
            'duration' => $plan->plan->duration,
            'price' => (float) $plan->price,

            'productName' => $tree->name,
            'image' => null,

            'name' => $this->options['dedication']['name'] ?? null,
            'occasion' => $this->options['dedication']['occasion'] ?? null,
            'message' => $this->options['dedication']['message'] ?? null,

            'item' => [
                'tree' => [
                    'id' => $tree->id,
                    'name' => $tree->name,
                    'plan' => [
                        'name' => $plan->plan->name,
                        'duration' => $plan->plan->duration,
                    ],
                ],
            ],

            'metadata' => [
                'location_id' => $this->options['location_id'] ?? null,
                'state_id' => $this->options['state_id'] ?? null,
            ],
        ];
    }
}