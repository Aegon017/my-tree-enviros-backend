<?php

namespace App\Repositories;

use App\Models\Wishlist;
use App\Models\WishlistItem;

class WishlistRepository
{
    public function getUserWishlist(int $userId): Wishlist
    {
        return Wishlist::firstOrCreate(['user_id' => $userId]);
    }

    public function findItem(Wishlist $wishlist, int $itemId)
    {
        return $wishlist->items()->where('id', $itemId)->first();
    }

    public function exists(Wishlist $wishlist, int $productId, ?int $variantId)
    {
        return $wishlist->items()
            ->where('product_id', $productId)
            ->when($variantId, fn($q) => $q->where('product_variant_id', $variantId))
            ->exists();
    }

    public function addItem(Wishlist $wishlist, array $data)
    {
        return $wishlist->items()->create($data);
    }

    public function deleteItem(WishlistItem $item)
    {
        return $item->delete();
    }

    public function clear(Wishlist $wishlist)
    {
        return $wishlist->items()->delete();
    }
}