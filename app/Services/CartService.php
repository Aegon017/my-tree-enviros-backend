<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\Api\V1\CartResource;
use App\Models\Cart;
use App\Models\PlanPrice;
use App\Models\ProductVariant;
use App\Repositories\CartRepository;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class CartService
{
    use ResponseHelpers;

    public function __construct(private CartRepository $repo) {}

    public function getCart(?int $userId, ?string $sessionId = null): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId, $sessionId);

        $cart->load([
            'items.tree.planPrices.plan',
            'items.productVariant.inventory.product',
            'items.productVariant.media',
            'items.planPrice.plan',
            'items.dedication',
        ]);

        return $this->success(['cart' => new CartResource($cart)]);
    }

    public function countUserCartItems(?int $userId, ?string $sessionId = null): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId, $sessionId);
        $count = $cart->items()->sum('quantity');

        return $this->success(['count' => (int) $count]);
    }

    public function addToUserCart(?int $userId, array $data, ?string $sessionId = null): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId, $sessionId);

        return $this->addItem($cart, $data);
    }

    public function updateUserCartItem(?int $userId, int $itemId, array $data, ?string $sessionId = null): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId, $sessionId);

        $item = $cart->items()->findOrFail($itemId);

        if (isset($data['quantity'])) {
            $item->quantity = $data['quantity'];
        }

        if (isset($data['plan_price_id'])) {
            $planPrice = PlanPrice::findOrFail($data['plan_price_id']);

            $item->plan_price_id = $planPrice->id;
            $item->plan_id = $planPrice->plan_id;
            $item->amount = (float) $planPrice->price;
        }

        if (array_key_exists('initiative_site_id', $data)) {
            $item->initiative_site_id = $data['initiative_site_id'];
        }

        if ($item->plan_price_id) {
            $price = PlanPrice::find($item->plan_price_id)?->price ?? $item->amount;
            $item->total_amount = $price * $item->quantity;
        } elseif ($item->product_variant_id) {
            $variant = ProductVariant::find($item->product_variant_id);
            if ($variant) {
                $price = $variant->selling_price ?? $variant->original_price;
                $item->amount = $price;
                $item->total_amount = $price * $item->quantity;
            }
        }

        $item->save();

        return $this->success(null, 'Cart updated successfully');
    }

    public function removeUserCartItem(?int $userId, int $itemId, ?string $sessionId = null): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId, $sessionId);
        $cart->items()->where('id', $itemId)->delete();

        return $this->success(null, 'Item removed from cart');
    }

    public function clearUserCart(?int $userId, ?string $sessionId = null): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId, $sessionId);
        $cart->items()->delete();

        return $this->success(null, 'Cart cleared successfully');
    }

    public function mergeGuestCart(string $sessionId, int $userId): void
    {
        // 1. Find Guest Cart
        $guestCart = Cart::where('session_id', $sessionId)
            ->where('status', Cart::STATUS_ACTIVE)
            ->first();

        if (! $guestCart) {
            return;
        }

        // 2. Find or Create User Cart
        $userCart = $this->repo->getOrCreate($userId);

        // 3. Merge Items
        DB::transaction(function () use ($guestCart, $userCart) {
            foreach ($guestCart->items as $guestItem) {
                // Check if identical item exists in user cart
                // Note: Complex matching logic might be needed depending on business rules. 
                // For now, based on simplified conflict resolution (DB constraints or basic logic).

                // We attempt to find a matching item in the user cart to just increase quantity.
                // If the item is entirely unique (like dedicated tree), we might just move it.
                // If it's a product, we sum quantity.

                // Simple strict matching for products:
                $match = null;
                if ($guestItem->product_variant_id) {
                    $match = $userCart->items()
                        ->where('product_variant_id', $guestItem->product_variant_id)
                        ->where('type', $guestItem->type)
                        ->first();
                }

                // Logic can be expanded for Trees/Plans as needed.

                if ($match) {
                    $match->quantity += $guestItem->quantity;
                    $match->amount = $guestItem->amount; // Update price if changed?
                    $match->total_amount = $match->quantity * $match->amount;
                    $match->save();
                    $guestItem->delete();
                } else {
                    // Reassign owner
                    $guestItem->cart_id = $userCart->id;
                    $guestItem->save();
                }
            }

            // 4. Delete or Deactivate Guest Cart
            $guestCart->delete(); // or $guestCart->update(['status' => 'merged']);
        });
    }

    private function addSponsorTree(Cart $cart, array $data): JsonResponse
    {
        $planPrice = PlanPrice::findOrFail($data['plan_price_id']);

        $amount = (float) $planPrice->price;
        $total = $amount * $data['quantity'];

        $item = $cart->items()->create([
            'type' => 'sponsor',
            'tree_id' => $data['tree_id'],
            'plan_id' => $planPrice->plan_id,
            'plan_price_id' => $planPrice->id,
            'initiative_site_id' => $data['initiative_site_id'] ?? null,
            'quantity' => $data['quantity'],
            'amount' => $amount,
            'total_amount' => $total,
        ]);

        if (! empty($data['dedication'])) {
            $item->dedication()->create($data['dedication']);
        }

        return $this->success(['item' => new \App\Http\Resources\Api\V1\CartItemResource($item)], 'Item added to cart');
    }

    private function addAdoptTree(Cart $cart, array $data): JsonResponse
    {
        $planPrice = PlanPrice::findOrFail($data['plan_price_id']);
        $instance = \App\Models\TreeInstance::findOrFail($data['tree_instance_id']);

        $amount = (float) $planPrice->price;
        $quantity = 1;
        $total = $amount;

        $item = $cart->items()->create([
            'type' => 'adopt',
            'tree_id' => $instance->tree_id,
            'tree_instance_id' => $instance->id,
            'plan_id' => $planPrice->plan_id,
            'plan_price_id' => $planPrice->id,
            'quantity' => $quantity,
            'amount' => $amount,
            'total_amount' => $total,
        ]);

        if (! empty($data['dedication'])) {
            $item->dedication()->create($data['dedication']);
        }

        return $this->success(['item' => new \App\Http\Resources\Api\V1\CartItemResource($item)], 'Item added to cart');
    }

    private function addProduct(Cart $cart, array $data): JsonResponse
    {
        $variant = ProductVariant::findOrFail($data['product_variant_id']);

        $amount = $variant->selling_price ?? $variant->original_price;
        $total = $amount * $data['quantity'];

        $item = $cart->items()->create([
            'type' => 'product',
            'product_variant_id' => $variant->id,
            'quantity' => $data['quantity'],
            'amount' => $amount,
            'total_amount' => $total,
        ]);

        return $this->success(['item' => new \App\Http\Resources\Api\V1\CartItemResource($item)], 'Item added to cart');
    }

    private function addItem(Cart $cart, array $data): JsonResponse
    {
        return DB::transaction(function () use ($cart, $data): JsonResponse {

            if ($data['type'] === 'product') {
                return $this->addProduct($cart, $data);
            }

            if ($data['type'] === 'sponsor') {
                return $this->addSponsorTree($cart, $data);
            }

            if ($data['type'] === 'adopt') {
                return $this->addAdoptTree($cart, $data);
            }

            return $this->error('Invalid item type');
        });
    }
}
