<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WishlistRequest;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use App\Traits\ResponseHelpers;

class WishlistController extends Controller
{
    use ResponseHelpers;

    public function __construct(private WishlistService $service) {}

    public function index(Request $request)
    {
        return $this->success($this->service->get($request->user()));
    }

    public function store(WishlistRequest $request)
    {
        $data = $request->validated();

        return $this->success($this->service->add($request->user(), $data));
    }

    public function destroy(Request $request, string $id)
    {
        $res = $this->service->remove($request->user(), (int)$id);

        return $this->success($res);
    }

    public function clear(Request $request)
    {
        $res = $this->service->clear($request->user());

        return $this->success(['wishlist' => $res['wishlist']], 'Cleared');
    }

    public function moveToCart(Request $request, string $id)
    {
        $res = $this->service->remove($request->user(), (int) $id);

        $wishlistItem = $this->service->getLastRemovedItem();

        $cartController = new CartController();
        $cartRequest = new Request([
            'item_type' => 'product',
            'product_id' => $wishlistItem->product_id,
            'product_variant_id' => $wishlistItem->product_variant_id,
            'quantity' => 1
        ]);

        $cartRequest->setUserResolver(fn() => $request->user());

        $cartResponse = $cartController->store($cartRequest);

        return $this->success([
            'wishlist' => $res,
            'cart' => $cartResponse->getData()->data->cart ?? null
        ], 'Item moved to cart');
    }
}
