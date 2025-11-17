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

    public function getCart(int $userId): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId);
        return $this->success(['cart' => new CartResource($cart->load('items.dedication'))]);
    }

    public function addToUserCart(int $userId, array $data): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId);
        return $this->addItem($cart, $data);
    }

    private function addItem(Cart $cart, array $data): JsonResponse
    {
        return DB::transaction(function () use ($cart, $data) {

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

    protected function addSponsorTree(Cart $cart, array $data): JsonResponse
    {
        $planPrice = PlanPrice::findOrFail($data['plan_price_id']);

        $amount = (float) $planPrice->price;
        $total = $amount * $data['quantity'];

        $item = $cart->items()->create([
            'type'          => 'sponsor',
            'tree_id'       => $data['tree_id'],
            'plan_id'       => $planPrice->plan_id,
            'plan_price_id' => $planPrice->id,
            'quantity'      => $data['quantity'],
            'amount'        => $amount,
            'total_amount'  => $total,
        ]);

        if (!empty($data['dedication'])) {
            $item->dedication()->create($data['dedication']);
        }

        return $this->success(['cart' => new CartResource($cart->load('items.dedication'))]);
    }

    protected function addAdoptTree(Cart $cart, array $data): JsonResponse
    {
        $planPrice = PlanPrice::findOrFail($data['plan_price_id']);

        $amount = (float) $planPrice->price;
        $total = $amount * $data['quantity'];

        $item = $cart->items()->create([
            'type'          => 'adopt',
            'tree_id'       => $data['tree_id'],
            'plan_id'       => $planPrice->plan_id,
            'plan_price_id' => $planPrice->id,
            'quantity'      => $data['quantity'],
            'amount'        => $amount,
            'total_amount'  => $total,
        ]);

        if (!empty($data['dedication'])) {
            $item->dedication()->create($data['dedication']);
        }

        return $this->success(['cart' => new CartResource($cart->load('items.dedication'))]);
    }

    protected function addProduct(Cart $cart, array $data): JsonResponse
    {
        $variant = ProductVariant::findOrFail($data['product_variant_id']);

        $amount = $variant->selling_price ?? $variant->original_price;
        $total = $amount * $data['quantity'];

        $cart->items()->create([
            'type'               => 'product',
            'product_variant_id' => $variant->id,
            'quantity'           => $data['quantity'],
            'amount'             => $amount,
            'total_amount'       => $total,
        ]);

        return $this->success(['cart' => new CartResource($cart->load('items.dedication'))]);
    }

    public function updateUserCartItem(int $userId, int $itemId, array $data): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId);

        $item = $cart->items()->findOrFail($itemId);

        if (isset($data['quantity'])) {
            $item->quantity = $data['quantity'];
        }

        if ($item->plan_price_id) {
            $price = PlanPrice::find($item->plan_price_id)?->price ?? $item->amount;
            $item->total_amount = $price * $item->quantity;
        }

        $item->save();

        return $this->success(['cart' => new CartResource($cart->load('items.dedication'))]);
    }

    public function removeUserCartItem(int $userId, int $itemId): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId);
        $cart->items()->where('id', $itemId)->delete();

        return $this->success(['cart' => new CartResource($cart->load('items.dedication'))]);
    }

    public function clearUserCart(int $userId): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId);
        $cart->items()->delete();

        return $this->success(['cart' => new CartResource($cart->load('items.dedication'))]);
    }
}