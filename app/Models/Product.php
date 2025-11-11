<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\ProductObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([ProductObserver::class])]
final class Product extends Model
{
    public $in_wishlist = false;
    
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    protected function sellingPrice(): Attribute
    {
        return Attribute::make(
            get: function () {
                $variant = $this->inventory?->productVariants
                    ?->filter(fn($v) => (float) $v->base_price > 0)
                    ->sortBy(function ($v) {
                        return $v->discount_price && $v->discount_price > 0
                            ? $v->discount_price
                            : $v->base_price;
                    })
                    ->first();

                if (! $variant) {
                    return 0.0;
                }

                return $variant->discount_price && $variant->discount_price > 0
                    ? (float) $variant->discount_price
                    : (float) $variant->base_price;
            }
        );
    }

    protected function originalPrice(): Attribute
    {
        return Attribute::make(
            get: function () {
                $variant = $this->inventory?->productVariants
                    ?->filter(fn($v) => (float) $v->base_price > 0)
                    ->sortBy(function ($v) {
                        return $v->discount_price && $v->discount_price > 0
                            ? $v->discount_price
                            : $v->base_price;
                    })
                    ->first();

                if (! $variant) {
                    return null;
                }

                return $variant->discount_price && $variant->discount_price > 0
                    ? (float) $variant->base_price
                    : null;
            }
        );
    }
}
