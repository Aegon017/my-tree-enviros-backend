<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TreeInstanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestCondition = $this->conditionUpdates->first();
        $latestGeo = $this->geotags->first();

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'status' => $this->status,
            'species' => $this->whenLoaded('tree', fn () => $this->tree->species),
            'location_id' => $this->location_id,
            'geotag' => $latestGeo ? [
                'latitude' => (float) $latestGeo->latitude,
                'longitude' => (float) $latestGeo->longitude,
            ] : null,
            'latest_condition' => $latestCondition ? [
                'condition' => $latestCondition->condition,
                'notes' => $latestCondition->notes,
                'reported_at' => $latestCondition->reported_at?->toDateString(),
                'media' => $latestCondition->getMedia('condition_images')->map(fn ($m) => $m->getFullUrl()),
            ] : null,
            'history' => $this->whenLoaded('history', fn () => $this->history->map(fn ($h): array => [
                'type' => $h->type,
                'start_date' => $h->start_date->toDateString(),
                'end_date' => $h->end_date->toDateString(),
                'user' => $h->user?->only(['id', 'name']),
                'plan' => $h->plan?->only(['id', 'duration', 'duration_unit']),
            ])),
            'status_logs' => $this->whenLoaded('statusLogs', fn () => $this->statusLogs->map(fn ($s): array => [
                'status' => $s->status,
                'notes' => $s->notes,
                'created_at' => $s->created_at->toDateTimeString(),
            ])),
        ];
    }
}
