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
            'nick_name' => $this->nick_name ?? $this->name,
            'botanical_name' => $this->botanical_name ?? '',
            'slug' => $this->slug,
            'sku' => $this->sku,
            'category' => [
                'id' => $this->productCategory->id,
                'name' => $this->productCategory->name,
                'slug' => $this->productCategory->slug,
                'image_url' => $this->productCategory->getFirstMediaUrl('images'),
            ],
            'description' => $this->description ?? '',
            'short_description' => $this->short_description ?? '',
            'rating' => (float) ($this->average_rating ?? 0),
            'review_count' => (int) ($this->reviews()->count() ?? 0),
            'variants' => ProductVariantResource::collection($this->inventory->productVariants),
        ];
    }
}
