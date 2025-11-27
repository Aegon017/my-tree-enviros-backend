<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;

final class ProductObserver
{
    public function saving(Product $product): void
    {
        $variants = $product->inventory?->productVariants;

        if (! $variants || $variants->isEmpty()) {
            $product->selling_price = 0;
            $product->original_price = null;

            return;
        }

        $variant = $variants
            ->filter(fn ($v): bool => (float) $v->original_price > 0)
            ->sortBy(fn ($v) => $v->selling_price && $v->selling_price > 0
                ? $v->selling_price
                : $v->original_price)
            ->first();

        if (! $variant) {
            $product->selling_price = 0;
            $product->original_price = null;

            return;
        }

        $selling_price = $variant->selling_price && $variant->selling_price > 0
            ? (float) $variant->selling_price
            : (float) $variant->original_price;

        $original_price = $variant->selling_price && $variant->selling_price > 0
            ? (float) $variant->original_price
            : null;

        $product->selling_price = $selling_price;
        $product->original_price = $original_price;
    }
}
