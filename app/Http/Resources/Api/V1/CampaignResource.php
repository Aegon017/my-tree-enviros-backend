<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location_id' => $this->location_id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'description' => $this->description,
            'amount' => $this->amount !== null ? (float) $this->amount : null,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'is_active' => (bool) $this->is_active,
            'thumbnail_url' => $this->getFirstMediaUrl('thumbnail') ?: null,
            'image_urls'    => $this->getMedia('images')
                ->map(fn($media) => $media->getFullUrl())
                ->filter()
                ->values()
                ->all(),
            'location' => $this->whenLoaded('location', fn() => [
                'id' => $this->location->id,
                'name' => $this->location->name,
                'parent_id' => $this->location->parent_id,
                'is_active' => (bool) $this->location->is_active,
            ])
        ];
    }
}
