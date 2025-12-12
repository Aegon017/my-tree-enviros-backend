<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\PlanTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TreeInstanceDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tree = $this->tree;

        return [
            'id' => $this->id,
            'sku' => $this->sku,

            'name' => $tree->name ?? 'Unknown Tree',
            'slug' => $tree->slug ?? '',
            'description' => $tree->description ?? '',

            'thumbnail_url' => $tree?->getFirstMedia('thumbnails')?->getFullUrl(),
            'image_urls' => $tree?->getMedia('images')->map(
                fn($media) => $media->getFullUrl()
            ),

            'plan_prices' => PlanPriceResource::collection(
                $tree->planPrices
                    ->where(fn($planPrice) => $planPrice->plan?->type === PlanTypeEnum::ADOPT)
                    ->load('plan')
            ),

            'location' => [
                'id' => $this->location->id ?? null,
                'name' => $this->location->name ?? null,
                'latitude' => $this->location->latitude ?? null,
                'longitude' => $this->location->longitude ?? null,
            ],

            'status' => $this->status,
            'adoptable_count' => 1,
            'planted_at' => $this->planted_at?->toDateString(),
        ];
    }
}
