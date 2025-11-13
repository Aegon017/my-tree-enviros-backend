<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;

class CartRepository
{
    public function getOrCreate(int $userId): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => $userId],
            ['expires_at' => now()->addDays(7)]
        );
    }

    public function getCartWithRelations(Cart $cart): Cart
    {
        return $cart->load([
            'items.cartable' => function ($query) {
                $model = $query->getModel();
                if ($model instanceof Product) {
                    $query->with([
                        'inventory.productVariants.variant.color',
                        'inventory.productVariants.variant.size',
                        'inventory.productVariants.variant.planter',
                        'productCategory',
                    ]);
                } elseif ($model instanceof ProductVariant) {
                    $query->with('inventory.product');
                }
            },
        ]);
    }

    public function addItem(Cart $cart, array $attributes): CartItem
    {
        return CartItem::create([
            'cart_id' => $cart->id,
            'cartable_type' => $attributes['cartable_type'],
            'cartable_id' => $attributes['cartable_id'],
            'quantity' => $attributes['quantity'] ?? 1,
            'price' => $attributes['price'] ?? 0,
            'options' => $attributes['options'] ?? [],
        ]);
    }

    public function findItem(Cart $cart, int $id): ?CartItem
    {
        return $cart->items()->where('id', $id)->first();
    }

    public function findExistingItem(Cart $cart, string $type, int $id): ?CartItem
    {
        return $cart->items()
            ->where('cartable_type', $type)
            ->where('cartable_id', $id)
            ->first();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }
}