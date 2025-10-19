<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'items' => WishlistItemResource::collection($this->whenLoaded('items')),
            'total_items' => $this->totalItems(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
