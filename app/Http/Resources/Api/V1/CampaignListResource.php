<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CampaignListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location_id' => $this->location_id,
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'target_amount' => $this->target_amount !== null ? (float) $this->target_amount : null,
            'raised_amount' => (float) $this->raised_amount,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => (bool) $this->is_active,
            'thumbnail_url' => $this->getFirstMedia('thumbnails')?->getFullUrl(),
            'location' => $this->whenLoaded('location', fn (): array => [
                'id' => $this->location->id,
                'name' => $this->location->name,
                'parent_id' => $this->location->parent_id,
                'is_active' => (bool) $this->location->is_active,
            ]),
        ];
    }
}
