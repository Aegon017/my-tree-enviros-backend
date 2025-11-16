<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\Api\V1\CartResource;
use App\Models\{Cart, Plan, PlanPrice, ProductVariant, TreeInstance, TreePlanPrice};
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
        return $this->success(['cart' => new CartResource($cart->load('items'))]);
    }

    public function addToUserCart(int $userId, array $data): JsonResponse
    {
        $cart = $this->repo->getOrCreate($userId);
        return $this->addItem($cart, $data);
    }

    private function addItem(Cart $cart, array $data): JsonResponse
    {
        return DB::transaction(function () use ($cart, $data) {
            switch ($data['type']) {
                case 'product':
                    return $this->addProduct($cart, $data);

                case 'sponsor':
                    return $this->addSponsorTree($cart, $data);

                case 'adopt':
                    return $this->addAdoptTree($cart, $data);
            }
            return $this->error('Invalid item type');
        });
    }

    protected function addSponsorTree($cart, $data)
    {
        $planPrice = PlanPrice::findOrFail($data['plan_price_id']);

        $amount = $planPrice->price;
        $total  = $amount * $data['quantity'];

        $item = $cart->items()->create([
            'type'              => 'sponsor',
            'tree_id'           => $data['tree_id'],
            'plan_id'           => $data['plan_id'],
            'plan_price_id'     => $data['plan_price_id'],
            'quantity'          => $data['quantity'],
            'amount'            => $amount,
            'total_amount'      => $total,
        ]);

        if (!empty($data['dedication'])) {
            $item->dedication()->create($data['dedication']);
        }

        return $this->success(['item' => $item]);
    }

    protected function addAdoptTree($cart, $data)
    {
        $planPrice = PlanPrice::findOrFail($data['plan_price_id']);

        $amount = $planPrice->price;
        $total  = $amount * $data['quantity'];

        $item = $cart->items()->create([
            'type'          => 'adopt',
            'tree_id'       => $data['tree_id'],
            'plan_id'       => $data['plan_id'],
            'plan_price_id' => $data['plan_price_id'],
            'quantity'      => $data['quantity'],
            'amount'        => $amount,
            'total_amount'  => $total,
        ]);

        if (!empty($data['dedication'])) {
            $item->dedication()->create($data['dedication']);
        }

        return $this->success(['item' => $item]);
    }


    protected function addProduct(Cart $cart, array $data)
    {
        $variant = ProductVariant::findOrFail($data['product_variant_id']);

        $amount = $variant->selling_price ?? $variant->original_price;
        $total = $amount * $data['quantity'];

        $item = $cart->items()->create([
            'type'              => 'product',
            'product_variant_id' => $variant->id,
            'quantity'          => $data['quantity'],
            'amount'            => $amount,
            'total_amount'      => $total,
        ]);

        return response()->json(['added' => true, 'item' => $item]);
    }
}
