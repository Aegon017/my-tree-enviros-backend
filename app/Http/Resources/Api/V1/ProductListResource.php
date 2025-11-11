<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $inventory = $this->inventory;

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

            'selling_price' => $this->selling_price,
            'original_price' => $this->original_price,

            'rating' => (float) ($this->rating ?? 0),
            'review_count' => (int) ($this->review_count ?? 0),
            'has_variants' => $hasVariants,
            'in_wishlist' => (bool) $this->in_wishlist,
        ];
    }
}
