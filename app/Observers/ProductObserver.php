<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    public function saving(Product $product): void
    {
        $variants = $product->inventory?->productVariants;

        if (!$variants || $variants->isEmpty()) {
            $product->selling_price = 0;
            $product->original_price = null;
            return;
        }

        $variant = $variants
            ->filter(fn($v) => (float) $v->base_price > 0)
            ->sortBy(function ($v) {
                return $v->discount_price && $v->discount_price > 0
                    ? $v->discount_price
                    : $v->base_price;
            })
            ->first();

        if (!$variant) {
            $product->selling_price = 0;
            $product->original_price = null;
            return;
        }

        $selling_price = $variant->discount_price && $variant->discount_price > 0
            ? (float) $variant->discount_price
            : (float) $variant->base_price;

        $original_price = $variant->discount_price && $variant->discount_price > 0
            ? (float) $variant->base_price
            : null;

        $product->selling_price = $selling_price;
        $product->original_price = $original_price;
    }
}
