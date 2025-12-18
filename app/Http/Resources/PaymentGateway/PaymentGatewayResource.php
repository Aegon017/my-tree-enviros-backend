<?php

declare(strict_types=1);

namespace App\Http\Resources\PaymentGateway;

use Illuminate\Http\Resources\Json\JsonResource;

final class PaymentGatewayResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->getFirstMedia('images')?->getFullUrl(),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'sort' => $this->sort,
        ];
    }
}
