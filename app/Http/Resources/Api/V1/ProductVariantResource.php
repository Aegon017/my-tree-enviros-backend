<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductVariantResource extends JsonResource
{
    /**
     * @return array
     */
    public function toArray(Request $request): array
    {
        $variant = $this->whenLoaded('variant');

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'inventory_id' => $this->inventory_id,
            'variant_id' => $this->variant_id,
            'variant' => $this->when($this->relationLoaded('variant'), fn(): array => [
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
            ], null),
            'variant_name' => $this->when(
                $this->relationLoaded('variant') && $variant,
                fn(): string => trim(collect([
                    $variant?->color?->name,
                    $variant?->size?->name,
                    $variant?->planter?->name,
                ])->filter()->implode(' ')),
                ''
            ),
            'product' => $this->when($this->relationLoaded('inventory.product'), fn(): array => [
                'id' => $this->inventory->product->id ?? null,
                'name' => $this->inventory->product->name ?? null,
                'slug' => $this->inventory->product->slug ?? null,
            ], null),
            'base_price' => (float) $this->base_price,
            'discount_price' => $this->discount_price !== null ? (float) $this->discount_price : null,
            'price' => (float) $this->price,
            'formatted_price' => '₹' . number_format($this->price, 2),
            'stock_quantity' => (int) $this->stock_quantity,
            'is_instock' => (bool) $this->is_instock,
            'image_urls' => $this->getMedia('images')->map(fn($m) => [
                'id' => $m->id,
                'url' => $m->getUrl(),
            ])->toArray(),
            'in_wishlist' => $this->in_wishlist ?? false,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
