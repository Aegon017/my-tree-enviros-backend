<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WishlistItem extends Model
{
    protected $fillable = [
        'wishlist_id',
        'product_id',
        'product_variant_id',
    ];

    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Check if this is a product variant item
     */
    public function isVariant(): bool
    {
        return $this->product_variant_id !== null;
    }

    /**
     * Get the actual product (variant or base product)
     */
    public function getProduct()
    {
        if ($this->isVariant()) {
            return $this->productVariant;
        }

        return $this->product;
    }

    /**
     * Get product name
     */
    public function getProductName(): string
    {
        if ($this->isVariant() && $this->productVariant) {
            $product = $this->productVariant->inventory->product ?? null;
            $variantInfo = mb_trim(sprintf('%s %s', $this->productVariant->color, $this->productVariant->size));

            return $product ? sprintf('%s (%s)', $product->name, $variantInfo) : sprintf('Product (%s)', $variantInfo);
        }

        return $this->product->name ?? 'Product';
    }

    /**
     * Get product image
     */
    public function getProductImage(): ?string
    {
        if ($this->isVariant() && $this->productVariant) {
            $product = $this->productVariant->inventory->product ?? null;

            return $product ? $product->getFirstMediaUrl('images') : null;
        }

        return $this->product?->getFirstMediaUrl('images');
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(): bool
    {
        if ($this->isVariant() && $this->productVariant) {
            return $this->productVariant->inventory?->is_instock ?? false;
        }

        return $this->product?->inventory?->is_instock ?? false;
    }

    /**
     * Get stock quantity
     */
    public function getStockQuantity(): int
    {
        if ($this->isVariant() && $this->productVariant) {
            return $this->productVariant->inventory?->stock_quantity ?? 0;
        }

        return $this->product?->inventory?->stock_quantity ?? 0;
    }
}
