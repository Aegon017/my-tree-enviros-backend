<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{Cart, CartItem, ProductVariant, TreeInstance, TreePlanPrice};
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
        return $this->success(['cart' => new \App\Http\Resources\Api\V1\CartResource($cart->load('items'))]);
    }

    public function addToUserCart(int $userId, array $data): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId);
        return $this->addItem($cart, $data);
    }

    private function addItem(Cart $cart, array $data): JsonResponse
    {
        return DB::transaction(function () use ($cart, $data) {
            if ($data['item_type'] === 'product') {
                return $this->addProductVariant($cart, $data);
            }
            if ($data['item_type'] === 'tree') {
                return $this->addTree($cart, $data);
            }
            return $this->error('Invalid item type');
        });
    }

    private function addProductVariant(Cart $cart, array $data): JsonResponse
    {
        $variant = ProductVariant::with(['inventory.product', 'variant.color', 'variant.size', 'variant.planter'])
            ->find($data['product_variant_id']);

        if (!$variant) {
            return $this->error('Invalid variant');
        }

        $existing = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($existing) {
            $existing->update(['quantity' => $existing->quantity + ($data['quantity'] ?? 1)]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_variant_id' => $variant->id,
                'quantity' => $data['quantity'] ?? 1,
            ]);
        }

        return $this->success(['cart' => new \App\Http\Resources\Api\V1\CartResource($cart->fresh('items'))], 'Added');
    }

    private function addTree(Cart $cart, array $data): JsonResponse
    {
        $instance = TreeInstance::find($data['tree_instance_id']);
        $plan = TreePlanPrice::with('plan')->find($data['tree_plan_price_id']);

        if (!$instance || !$plan) {
            return $this->error('Invalid tree item');
        }

        $exists = CartItem::where('cart_id', $cart->id)
            ->where('tree_instance_id', $instance->id)
            ->exists();

        if ($exists) {
            return $this->error('Tree already in cart');
        }

        CartItem::create([
            'cart_id' => $cart->id,
            'tree_instance_id' => $instance->id,
            'tree_plan_price_id' => $plan->id,
            'quantity' => 1,
            'options' => [
                'dedication' => [
                    'name' => $data['name'] ?? null,
                    'occasion' => $data['occasion'] ?? null,
                    'message' => $data['message'] ?? null,
                ],
                'state_id' => $data['state_id'] ?? null,
                'location_id' => $data['location_id'] ?? null,
            ],
        ]);

        return $this->success(['cart' => new \App\Http\Resources\Api\V1\CartResource($cart->fresh('items'))], 'Added');
    }
}