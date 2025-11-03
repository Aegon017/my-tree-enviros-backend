<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variant = $this->whenLoaded('variant');

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'inventory_id' => $this->inventory_id,
            'variant_id' => $this->variant_id,
            'variant' => $this->when($this->relationLoaded('variant'), fn (): array => [
                'id' => $variant?->id,
                'color' => $variant?->color ? [
                    'id' => $variant->color->id,
                    'name' => $variant->color->name,
                    'code' => $variant->color->code,
                ] : null,
                'size' => $variant?->size ? [
                    'id' => $variant->size->id,
                    'name' => $variant->size->name,
                ] : null,
                'planter' => $variant?->planter ? [
                    'id' => $variant->planter->id,
                    'name' => $variant->planter->name,
                ] : null,
            ]),
            'variant_name' => $this->when(
                $this->relationLoaded('variant') && $variant,
                function () use ($variant): string {
                    $parts = [];
                    if ($variant?->color ?? null) {
                        $parts[] = $variant->color->name;
                    }

                    if ($variant?->size ?? null) {
                        $parts[] = $variant->size->name;
                    }

                    if ($variant?->planter ?? null) {
                        $parts[] = $variant->planter->name;
                    }

                    return mb_trim(implode(' ', $parts));
                },
                ''
            ),
            'product' => $this->when($this->relationLoaded('inventory.product'), fn (): array => [
                'id' => $this->inventory->product->id ?? null,
                'name' => $this->inventory->product->name ?? null,
                'slug' => $this->inventory->product->slug ?? null,
            ]),
            'base_price' => (float) $this->base_price,
            'discount_price' => $this->discount_price ? (float) $this->discount_price : null,
            'price' => (float) $this->price,
            'formatted_price' => '₹'.number_format($this->price, 2),
            'stock_quantity' => (int) $this->stock_quantity,
            'is_instock' => (bool) $this->is_instock,
            'images' => $this->getMedia('images')->map(fn ($media): array => [
                'id' => $media->id,
                'url' => $media->getUrl(),
            ])->toArray(),
            'in_wishlist' => $this->when(
                isset($this->in_wishlist),
                $this->in_wishlist ?? false
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
