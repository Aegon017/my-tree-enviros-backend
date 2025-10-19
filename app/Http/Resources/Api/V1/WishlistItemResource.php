<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WishlistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wishlist_id' => $this->wishlist_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'is_variant' => $this->isVariant(),
            'product_name' => $this->getProductName(),
            'product_image' => $this->getProductImage(),
            'product' => $this->when($this->relationLoaded('product') && !$this->isVariant(), function () {
                return new ProductResource($this->product);
            }),
            'product_variant' => $this->when($this->relationLoaded('productVariant') && $this->isVariant(), function () {
                return new ProductVariantResource($this->productVariant);
            }),
            'stock' => [
                'is_instock' => $this->isInStock(),
                'quantity' => $this->getStockQuantity(),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
