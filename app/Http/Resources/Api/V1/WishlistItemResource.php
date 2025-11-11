<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WishlistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;
        $variant = $this->productVariant;

        $price = $variant
            ? ($variant->discount_price ?? $variant->base_price ?? $variant->price ?? $product->selling_price)
            : $product->selling_price;

        $stockQuantity = $variant
            ? ($variant->stock_quantity ?? 0)
            : ($product?->inventory->stock_quantity ?? 0);

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'name' => $product->name,
            'price' => (float) $price,
            'image_url' => $variant?->getFirstMediaUrl('images') ?? null,
            'in_stock' => $stockQuantity > 0,
            'quantity' => (int) $stockQuantity,
        ];
    }
}
