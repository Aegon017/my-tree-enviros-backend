<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Build direct URLs for main image and thumbnail
        $mainImage =
            $this->getFirstMedia('images') ??
            $this->getFirstMedia('thumbnails');
        $mainImageUrl = null;
        if ($mainImage) {
            $mainImageUrl = $mainImage->getFullUrl();
        }

        $thumbnail =
            $this->getFirstMedia('thumbnails') ??
            $this->getFirstMedia('images');
        $thumbnailUrl = null;
        if ($thumbnail) {
            $thumbnailUrl = $thumbnail->getFullUrl();
        }

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'age' => $this->age,
            'age_unit' => $this->age_unit,
            'age_display' => $this->age.' '.ucfirst((string) $this->age_unit->value),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'main_image_url' => $mainImageUrl,
            'thumbnail' => $thumbnailUrl,
            'images' => $this->getMedia('images')->map(
                fn ($media): array => [
                    'id' => $media->id,
                    'image_url' => $media->getFullUrl(),
                ],
            ),
            'available_instances_count' => $this->whenCounted(
                'instances',
                fn () => $this->instances()
                    ->where('status', 'available')
                    ->count(),
            ),
            'plan_prices' => TreePlanPriceResource::collection(
                $this->whenLoaded('planPrices'),
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'locations' => LocationResource::collection(
                $this->whenLoaded(
                    'instances',
                    fn () => $this->instances
                        ->pluck('location')
                        ->filter()
                        ->unique('id'),
                ),
            ),
        ];
    }
}
