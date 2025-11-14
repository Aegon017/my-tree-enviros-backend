<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TreePricePlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'duration' => $this->duration,
            'duration_type' => $this->duration_type->value
        ];
    }
}
