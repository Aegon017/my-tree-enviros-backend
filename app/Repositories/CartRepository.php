<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;

class CartRepository
{
    public function getOrCreate(int $userId): Cart
    {
        return Cart::firstOrCreate(['user_id' => $userId]);
    }

    public function getWithRelations(Cart $cart): Cart
    {
        return $cart->load([
            'items.productVariant.inventory.product.productCategory',
            'items.productVariant.variant.color',
            'items.productVariant.variant.size',
            'items.productVariant.variant.planter',
            'items.treeInstance.tree',
            'items.treePlanPrice.plan',
        ]);
    }

    public function add(array $data): CartItem
    {
        return CartItem::create($data);
    }

    public function findById(Cart $cart, int $id): ?CartItem
    {
        return $cart->items()->where('id', $id)->first();
    }

    public function findProductVariant(Cart $cart, int $variantId): ?CartItem
    {
        return $cart->items()->where('product_variant_id', $variantId)->first();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }
}