<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TreeInstanceListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => ($this->tree->name ?? 'Unknown Tree') . ' (' . $this->sku . ')',
            'slug' => $this->tree->slug ?? '',
            'thumbnail_url' => $this->tree->getFirstMedia('thumbnails')?->getFullUrl(),
            'location' => [
                'id' => $this->location->id,
                'name' => $this->location->name,
            ],
            'plan_price' => $this->tree->planPrices->first()?->price ?? null,
        ];
    }
}
