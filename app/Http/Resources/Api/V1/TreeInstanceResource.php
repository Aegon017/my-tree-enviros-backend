<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TreeInstanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'coordinates' => $this->latitude && $this->longitude ? [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude,
            ] : null,
            'tree' => new TreeResource($this->whenLoaded('tree')),
            'location' => [
                'id' => $this->location->id ?? null,
                'name' => $this->location->name ?? null,
                'type' => $this->location->type ?? null,
            ],
            'tree_id' => $this->tree_id,
            'location_id' => $this->location_id,
            'media' => $this->whenLoaded('media', function () {
                return $this->media->map(fn($media) => [
                    'id' => $media->id,
                    'type' => $media->type,
                    'url' => $media->url,
                    'caption' => $media->caption,
                    'uploaded_at' => $media->created_at,
                ]);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
