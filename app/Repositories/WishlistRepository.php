<?php

namespace App\Repositories;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Builder;

class WishlistRepository
{
    public function getUserWishlist(int $userId): Wishlist
    {
        return Wishlist::firstOrCreate(['user_id' => $userId]);
    }

    public function findItem(Wishlist $wishlist, int $itemId)
    {
        return WishlistItem::where('wishlist_id', $wishlist->id)
            ->where('id', $itemId)
            ->first();
    }

    public function findExistingItem(Wishlist $wishlist, int $productId, ?int $variantId)
    {
        return WishlistItem::where('wishlist_id', $wishlist->id)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();
    }

    public function addItem(Wishlist $wishlist, int $productId, ?int $variantId)
    {
        return WishlistItem::create([
            'wishlist_id' => $wishlist->id,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
        ]);
    }

    public function deleteItem(WishlistItem $item)
    {
        return $item->delete();
    }

    public function clear(Wishlist $wishlist)
    {
        return $wishlist->items()->delete();
    }

    public function isProductInWishlist(Wishlist $wishlist, int $productId, ?int $variantId)
    {
        return WishlistItem::where('wishlist_id', $wishlist->id)
            ->where('product_id', $productId)
            ->when($variantId, fn(Builder $q) => $q->where('product_variant_id', $variantId))
            ->exists();
    }
}
