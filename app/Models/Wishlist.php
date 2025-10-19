<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Wishlist extends Model
{
    protected $fillable = [
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function totalItems(): int
    {
        return $this->items->count();
    }

    public function hasProduct(int $productId, ?int $variantId = null): bool
    {
        $query = $this->items()->where('product_id', $productId);

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        }

        return $query->exists();
    }

    public function clear(): void
    {
        $this->items()->delete();
    }
}
