<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use App\Observers\ProductObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([ProductObserver::class])]
#[ScopedBy([ActiveScope::class])]
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
            get: function (): float {
                $variant = $this->inventory?->productVariants
                    ?->filter(fn($v): bool => (float) $v->original_price > 0)
                    ->sortBy(fn($v) => $v->selling_price && $v->selling_price > 0
                        ? $v->selling_price
                        : $v->original_price)
                    ->first();

                if (! $variant) {
                    return 0.0;
                }

                return $variant->selling_price && $variant->selling_price > 0
                    ? (float) $variant->selling_price
                    : (float) $variant->original_price;
            }
        );
    }

    protected function originalPrice(): Attribute
    {
        return Attribute::make(
            get: function (): ?float {
                $variant = $this->inventory?->productVariants
                    ?->filter(fn($v): bool => (float) $v->original_price > 0)
                    ->sortBy(fn($v) => $v->selling_price && $v->selling_price > 0
                        ? $v->selling_price
                        : $v->original_price)
                    ->first();

                if (! $variant) {
                    return null;
                }

                return $variant->selling_price && $variant->selling_price > 0
                    ? (float) $variant->original_price
                    : null;
            }
        );
    }
}
