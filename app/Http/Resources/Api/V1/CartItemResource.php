<?php

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

        return [
            'id' => $this->id,
            'type' => 'product',
            'quantity' => $this->quantity,
            'price' => (float) $this->amount,

            'name' => $product->name,
            'image_url' => $variant->getFirstMedia('images')->getFullUrl(),

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

        return [
            'id' => $this->id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'duration' => $planPrice->plan->duration,
            'price' => (float) $this->amount,

            'tree' => [
                'id' => $tree->id,
                'name' => $tree->name,
            ],

            'plan' => [
                'id' => $planPrice->plan->id,
                'duration' => $planPrice->plan->duration,
            ],

            'dedication' => [
                'name' => $ded->name ?? null,
                'occasion' => $ded->occasion ?? null,
                'message' => $ded->message ?? null,
            ],
        ];
    }
}
