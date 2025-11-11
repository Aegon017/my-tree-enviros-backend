<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\WishlistRepository;
use Illuminate\Support\Facades\DB;

class WishlistService
{
    public function __construct(
        private WishlistRepository $wishlistRepository
    ) {}

    public function get($user)
    {
        $wishlist = $this->wishlistRepository->getUserWishlist($user->id);
        $wishlist->load(['items.product.inventory', 'items.productVariant.inventory.product']);
        return $wishlist;
    }

    public function add($user, array $data)
    {
        $wishlist = $this->wishlistRepository->getUserWishlist($user->id);

        $product = Product::where('id', $data['product_id'])
            ->where('is_active', true)
            ->first();

        if (! $product) return ['error' => true, 'message' => 'Product unavailable'];

        if (! empty($data['product_variant_id'])) {
            $variant = ProductVariant::where('id', $data['product_variant_id'])
                ->whereHas('inventory', fn($q) => $q->where('product_id', $data['product_id']))
                ->first();

            if (! $variant) return ['error' => true, 'message' => 'Invalid product variant'];
        }

        $existing = $this->wishlistRepository->findExistingItem($wishlist, $data['product_id'], $data['product_variant_id'] ?? null);

        if ($existing) return ['error' => true, 'message' => 'Already in wishlist'];

        DB::transaction(fn() => $this->wishlistRepository->addItem($wishlist, $data['product_id'], $data['product_variant_id'] ?? null));

        return $this->get($user);
    }

    public function remove($user, int $itemId)
    {
        $wishlist = $this->wishlistRepository->getUserWishlist($user->id);
        $item = $this->wishlistRepository->findItem($wishlist, $itemId);

        if (! $item) return false;

        DB::transaction(fn() => $this->wishlistRepository->deleteItem($item));

        return $this->get($user);
    }

    public function clear($user)
    {
        $wishlist = $this->wishlistRepository->getUserWishlist($user->id);

        DB::transaction(fn() => $this->wishlistRepository->clear($wishlist));

        return $this->get($user);
    }

    public function check($user, int $productId, ?int $variantId)
    {
        $wishlist = $this->wishlistRepository->getUserWishlist($user->id);
        return $this->wishlistRepository->isProductInWishlist($wishlist, $productId, $variantId);
    }
}
