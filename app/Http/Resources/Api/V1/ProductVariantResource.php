<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductVariantResource extends JsonResource
{
    /**
     * @return array
     */
    public function toArray(Request $request): array
    {
        $variant = $this->whenLoaded('variant');

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'variant' => $this->when($this->relationLoaded('variant'), fn(): array => [
                'id' => $variant?->id,
                'name' => $variant?->color?->name. ' ' . $variant?->size->name. ' ' . $variant?->planter->name,
                'color' => $variant?->color ? [
                    'name' => $variant->color->name,
                    'code' => $variant->color->code,
                ] : null,
                'size' => $variant?->size ? [
                    'name' => $variant->size->name,
                ] : null,
                'planter' => $variant?->planter ? [
                    'name' => $variant->planter->name,
                ] : null,
            ], null),
            'image_urls' => $this->getMedia('images')->map(fn($m) => ['url' => $m->getUrl()])->toArray(),
            'selling_price' => $this->selling_price && $this->selling_price > 0 ? (float) $this->selling_price : (float) $this->original_price,
            'original_price' => $this->selling_price && $this->selling_price > 0 ? (float) $this->original_price : null,
            'stock_quantity' => (int) $this->stock_quantity,
            'is_instock' => (bool) $this->is_instock,
            'in_wishlist' => (bool) $this->in_wishlist,
        ];
    }
}
