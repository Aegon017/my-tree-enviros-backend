<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    /**
     * @return array
     */
    public function toArray(Request $request): array
    {
        $inventory = $this->inventory;

        $variantWithMinPrice = $inventory?->productVariants
            ?->filter(fn ($v) => (float) $v->base_price > 0)
            ->sortBy('base_price')
            ->first();

        $price = $variantWithMinPrice
            ? (float) $variantWithMinPrice->base_price
            : 0.0;

        $discountPrice = $variantWithMinPrice && $variantWithMinPrice->discount_price !== null
            ? (float) $variantWithMinPrice->discount_price
            : null;

        $thumbnail_url = $inventory?->getFirstMediaUrl('thumbnail') ?? '';

        $hasVariants = $inventory?->productVariants->filter(
            fn($v) => $v->variant && ($v->variant->color || $v->variant->size || $v->variant->planter)
        )->isNotEmpty();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => [
                'id' => $this->productCategory->id ?? null,
                'name' => $this->productCategory->name ?? '',
            ],
            'thumbnail_url' => $thumbnail_url,
            'short_description' => $this->short_description ?? '',
            'price' => $price,
            'discount_price' => $discountPrice,
            'rating' => (float) ($this->rating ?? 0),
            'review_count' => (int) ($this->review_count ?? 0),
            'has_variants' => $hasVariants,
        ];
    }
}
