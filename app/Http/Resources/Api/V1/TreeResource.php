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
            'age_display' => $this->age . ' ' . ucfirst($this->age_unit->value),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'thumbnail' => $this->getFirstMediaUrl('thumbnails'),
            'images' => $this->getMedia('images')->map(fn($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
            ]),
            'available_instances_count' => $this->whenCounted('instances', function () {
                return $this->instances()->where('status', 'available')->count();
            }),
            'plan_prices' => TreePlanPriceResource::collection($this->whenLoaded('planPrices')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
