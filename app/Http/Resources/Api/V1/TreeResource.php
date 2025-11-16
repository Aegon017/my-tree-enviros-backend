<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'age' => $this->age,
            'age_unit' => $this->age_unit,
            'description' => $this->description,
            'thumbnail_url' => $this->getFirstMedia('thumbnails')->getFullUrl(),
            'image_urls' => $this->getMedia('images')->map(fn($media) => $media->getFullUrl()),
            'plan_prices' => PlanPriceResource::collection($this->whenLoaded('planPrices')),
            'adoptable_count' => $this->adoptable_count ?? 0,
        ];
    }
}
