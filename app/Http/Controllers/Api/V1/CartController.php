<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\CartStoreRequest;
use App\Http\Requests\Api\V1\CartUpdateRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class CartController
{
    public function __construct(private CartService $cartService) {}

    public function index(Request $request): JsonResponse
    {
        return $this->cartService->getCart($request->user()->id);
    }

    public function store(CartStoreRequest $request): JsonResponse
    {
        return $this->cartService->addToUserCart($request->user()->id, $request->validated());
    }

    public function update(CartUpdateRequest $request, string $id): JsonResponse
    {
        return $this->cartService->updateUserCartItem($request->user()->id, (int) $id, $request->validated());
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        return $this->cartService->removeUserCartItem($request->user()->id, (int) $id);
    }

    public function clear(Request $request): JsonResponse
    {
        return $this->cartService->clearUserCart($request->user()->id);
    }
}
