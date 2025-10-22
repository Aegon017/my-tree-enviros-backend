<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'botanical_name' => $this->botanical_name,
            'nick_name' => $this->nick_name,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'category' => [
                'id' => $this->productCategory->id ?? null,
                'name' => $this->productCategory->name ?? null,
                'slug' => $this->productCategory->slug ?? null,
            ],
            'images' => $this->getMedia('images')->map(fn($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'name' => $media->name,
            ]),
            'inventory' => $this->when($this->relationLoaded('inventory'), function () {
                return [
                    'id' => $this->inventory->id ?? null,
                    'stock_quantity' => $this->inventory->stock_quantity ?? 0,
                    'is_instock' => $this->inventory->is_instock ?? false,
                    'has_variants' => $this->inventory?->productVariants()->count() > 0,
                ];
            }),
            'variants' => ProductVariantResource::collection($this->whenLoaded('inventory.productVariants')),
            'price' => $this->price ?? null,
            'formatted_price' => $this->price ? '₹' . number_format($this->price, 2) : null,
            'in_wishlist' => $this->when(
                isset($this->in_wishlist),
                $this->in_wishlist ?? false
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
