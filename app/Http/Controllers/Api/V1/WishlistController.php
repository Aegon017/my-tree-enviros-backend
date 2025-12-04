<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WishlistRequest;
use App\Services\CartService;
use App\Services\WishlistService;
use App\Traits\ResponseHelpers;
use Illuminate\Http\Request;

final class WishlistController extends Controller
{
    use ResponseHelpers;

    public function __construct(private WishlistService $service) {}

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->success($this->service->get($request->user()));
    }

    public function store(WishlistRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        return $this->success($this->service->add($request->user(), $data));
    }

    public function destroy(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $res = $this->service->remove($request->user(), (int) $id);

        return $this->success($res);
    }

    public function clear(Request $request): \Illuminate\Http\JsonResponse
    {
        $res = $this->service->clear($request->user());

        return $this->success(['wishlist' => $res['wishlist']], 'Cleared');
    }

    public function moveToCart(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $wishlistResponse = $this->service->remove($user, (int) $id);
        $wishlistItem = $this->service->getLastRemovedItem();

        if (! $wishlistItem) {
            return $this->error('No item found to move', 404);
        }

        $cartData = [
            'type' => 'product',
            'product_variant_id' => $wishlistItem->product_variant_id,
            'quantity' => 1,
        ];

        $cartResponse = app(CartService::class)->addToUserCart($user->id, $cartData);

        return $this->success([
            'wishlist' => $wishlistResponse,
            'cart' => $cartResponse->getData()->data->cart ?? null,
        ], 'Item moved to cart');
    }
}
