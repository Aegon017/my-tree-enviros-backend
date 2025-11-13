<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CartStoreRequest;
use App\Http\Requests\Api\V1\CartUpdateRequest;
use App\Services\CartService;
use Illuminate\Http\Request;

final class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function index(Request $request)
    {
        return $this->cartService->getCart($request->user()->id);
    }

    public function store(CartStoreRequest $request)
    {
        return $this->cartService->addToUserCart($request->user()->id, $request->validated());
    }

    public function update(CartUpdateRequest $request, string $id)
    {
        return $this->cartService->updateUserCartItem($request->user()->id, (int) $id, $request->validated());
    }

    public function destroy(Request $request, string $id)
    {
        return $this->cartService->removeUserCartItem($request->user()->id, (int) $id);
    }

    public function clear(Request $request)
    {
        return $this->cartService->clearUserCart($request->user()->id);
    }
}