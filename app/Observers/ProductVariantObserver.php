<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ProductVariant;

final class ProductVariantObserver
{
    public function created(ProductVariant $variant): void
    {
        $variant->inventory?->product?->save();
    }

    public function updated(ProductVariant $variant): void
    {
        $variant->inventory?->product?->save();
    }

    public function deleted(ProductVariant $variant): void
    {
        $variant->inventory?->product?->save();
    }

    public function restored(ProductVariant $variant): void
    {
        $variant->inventory?->product?->save();
    }

    public function forceDeleted(ProductVariant $variant): void
    {
        $variant->inventory?->product?->save();
    }
}
