<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WishlistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => ProductResource::make($this->product),
            'variant' => ProductVariantResource::make($this->whenLoaded('productVariant', $this->productVariant)),
        ];
    }
}
