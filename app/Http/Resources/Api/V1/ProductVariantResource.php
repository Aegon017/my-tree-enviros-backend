<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'color' => $this->color,
            'size' => $this->size,
            'variant_name' => trim("{$this->color} {$this->size}"),
            'inventory_id' => $this->inventory_id,
            'product' => $this->when($this->relationLoaded('inventory.product'), function () {
                return [
                    'id' => $this->inventory->product->id ?? null,
                    'name' => $this->inventory->product->name ?? null,
                    'slug' => $this->inventory->product->slug ?? null,
                ];
            }),
            'stock_quantity' => $this->inventory->stock_quantity ?? 0,
            'is_instock' => $this->inventory->is_instock ?? false,
            'price' => $this->price ?? null,
            'formatted_price' => $this->price ? '₹' . number_format($this->price, 2) : null,
            'images' => $this->when($this->relationLoaded('inventory.product'), function () {
                $product = $this->inventory->product ?? null;
                return $product ? $product->getMedia('images')->map(fn($media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumb' => $media->getUrl('thumb'),
                ]) : [];
            }),
            'in_wishlist' => $this->when(
                isset($this->in_wishlist),
                $this->in_wishlist ?? false
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
