<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Services\ProductService;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductController extends Controller
{
    use ResponseHelpers;

    public function __construct(private readonly ProductService $service) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success($this->service->paginate($request));
    }

    public function show(Request $request, string $identifier): JsonResponse
    {
        $product = $this->service->findByIdOrSlugWithWishlist($request, $identifier);

        if (! $product) {
            return $this->notFound('Product not found');
        }

        return $this->success([
            'product' => new ProductResource($product),
        ]);
    }

    public function variants(Request $request, string $id): JsonResponse
    {
        $variants = $this->service->variants($request, $id);
        if (! $variants) {
            return $this->notFound('Product not found');
        }

        return $this->success($variants);
    }

    public function featured(Request $request): JsonResponse
    {
        return $this->success($this->service->featured($request));
    }

    public function byCategory(Request $request, string $categoryId): JsonResponse
    {
        return $this->success($this->service->byCategory($request, $categoryId));
    }
}
