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
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'duration' => $this->duration,
            'duration_type' => $this->duration_type->value,
            'duration_type_label' => $this->duration_type->label(),
            'duration_display' => $this->duration.' '.ucfirst((string) $this->duration_type->value),
            'features' => $this->features ?? [],
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
