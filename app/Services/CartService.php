<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{Cart, Product, ProductVariant, TreeInstance, TreePlanPrice};
use App\Repositories\CartRepository;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class CartService
{
    use ResponseHelpers;

    public function __construct(private CartRepository $repo) {}

    public function getCart(int $userId): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId);
        if ($cart->isExpired()) {
            $cart->clearExpiredItems();
        }

        return $this->success(['cart' => $this->repo->getCartWithRelations($cart)]);
    }

    public function addToUserCart(int $userId, array $data): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId);
        return $this->addItem($cart, $data);
    }

    public function updateUserCartItem(int $userId, int $itemId, array $data): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId);
        return $this->updateItem($cart, $itemId, $data);
    }

    public function removeUserCartItem(int $userId, int $itemId): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId);
        return $this->removeItem($cart, $itemId);
    }

    public function clearUserCart(int $userId): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId);
        return $this->clear($cart);
    }

    private function addItem(Cart $cart, array $validated): JsonResponse
    {
        return DB::transaction(function () use ($cart, $validated) {
            return match ($validated['item_type']) {
                'tree' => $this->addTreeToCart($cart, $validated),
                'product' => $this->addProductToCart($cart, $validated),
                default => $this->error('Invalid item type', 422),
            };
        });
    }

    private function addTreeToCart(Cart $cart, array $data): JsonResponse
    {
        $treeInstance = TreeInstance::with('tree')->find($data['tree_instance_id'] ?? null);
        if (! $treeInstance) {
            return $this->error('Tree instance not found', 404);
        }

        $treePlanPrice = TreePlanPrice::with('plan')->find($data['tree_plan_price_id'] ?? null);
        if (! $treePlanPrice || ! $treePlanPrice->is_active) {
            return $this->error('Invalid or inactive tree plan', 422);
        }

        $existing = $this->repo->findExistingItem($cart, TreeInstance::class, $treeInstance->id);
        if ($existing) {
            return $this->error('This tree is already in your cart', 422);
        }

        $this->repo->addItem($cart, [
            'cartable_type' => TreeInstance::class,
            'cartable_id' => $treeInstance->id,
            'quantity' => 1,
            'price' => $treePlanPrice->price,
            'options' => [
                'tree_plan_price_id' => $treePlanPrice->id,
                'plan_name' => $treePlanPrice->plan->name,
                'duration' => $treePlanPrice->plan->duration,
                'dedication' => [
                    'name' => $data['name'] ?? null,
                    'occasion' => $data['occasion'] ?? null,
                    'message' => $data['message'] ?? null,
                ],
            ],
        ]);

        return $this->success(['cart' => $this->repo->getCartWithRelations($cart)], 'Tree added to cart successfully');
    }

    private function addProductToCart(Cart $cart, array $data): JsonResponse
    {
        $quantity = $data['quantity'] ?? 1;

        if (! empty($data['product_variant_id'])) {
            $variant = ProductVariant::with('inventory.product')->find($data['product_variant_id']);
            if (! $variant) {
                return $this->error('Product variant not found', 404);
            }

            if (! $variant->is_instock || $variant->stock_quantity < $quantity) {
                return $this->error('Insufficient stock for this variant', 422);
            }

            $existing = $this->repo->findExistingItem($cart, ProductVariant::class, $variant->id);
            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $quantity]);
                return $this->success(['cart' => $this->repo->getCartWithRelations($cart)], 'Product quantity updated');
            }

            $this->repo->addItem($cart, [
                'cartable_type' => ProductVariant::class,
                'cartable_id' => $variant->id,
                'quantity' => $quantity,
                'price' => $variant->price ?? 0,
                'options' => [
                    'variant' => [
                        'sku' => $variant->sku,
                        'color' => $variant->color,
                        'size' => $variant->size,
                    ],
                    'product_name' => $variant->inventory->product->name ?? 'Product',
                ],
            ]);
        } else {
            $product = Product::with('inventory')->find($data['product_id']);
            if (! $product) {
                return $this->error('Product not found', 404);
            }

            if (! $product->inventory || ! $product->inventory->is_instock || $product->inventory->stock_quantity < $quantity) {
                return $this->error('Insufficient stock for this product', 422);
            }

            $existing = $this->repo->findExistingItem($cart, Product::class, $product->id);
            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $quantity]);
                return $this->success(['cart' => $this->repo->getCartWithRelations($cart)], 'Product quantity updated');
            }

            $this->repo->addItem($cart, [
                'cartable_type' => Product::class,
                'cartable_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price ?? 0,
                'options' => [
                    'product_name' => $product->name,
                    'sku' => $product->sku ?? null,
                ],
            ]);
        }

        return $this->success(['cart' => $this->repo->getCartWithRelations($cart)], 'Product added to cart successfully');
    }

    private function updateItem(Cart $cart, int $id, array $data): JsonResponse
    {
        $item = $this->repo->findItem($cart, $id);
        if (! $item) {
            return $this->notFound('Cart item not found');
        }

        if (isset($data['quantity'])) {
            $item->update(['quantity' => $data['quantity']]);
        }

        return $this->success(['cart' => $this->repo->getCartWithRelations($cart)], 'Cart item updated successfully');
    }

    private function removeItem(Cart $cart, int $id): JsonResponse
    {
        $item = $this->repo->findItem($cart, $id);
        if (! $item) {
            return $this->notFound('Cart item not found');
        }

        $item->delete();
        return $this->success(['cart' => $this->repo->getCartWithRelations($cart)], 'Item removed from cart');
    }

    private function clear(Cart $cart): JsonResponse
    {
        $this->repo->clear($cart);
        return $this->success(['cart' => $cart->fresh('items')], 'Cart cleared successfully');
    }
}
