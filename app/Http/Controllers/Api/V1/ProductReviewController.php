<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductReviewRequest;
use App\Http\Requests\Api\V1\UpdateProductReviewRequest;
use App\Services\ProductReviewService;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductReviewController extends Controller
{
    use ResponseHelpers;

    public function __construct(private readonly ProductReviewService $service) {}

    public function index(int $id): JsonResponse
    {
        return $this->success([
            'reviews' => $this->service->getProductReviews($id),
        ]);
    }

    public function store(int $id, StoreProductReviewRequest $request): JsonResponse
    {
        $reviews = $this->service->createReview(
            $id,
            $request->user()->id,
            $request->validated()
        );

        return $this->success(
            ['reviews' => $reviews],
            'Review added successfully'
        );
    }

    public function update(int $id, int $reviewId, UpdateProductReviewRequest $request): JsonResponse
    {
        $reviews = $this->service->updateReview(
            $id,
            $reviewId,
            $request->user()->id,
            $request->validated()
        );

        return $this->success(
            ['reviews' => $reviews],
            'Review updated successfully'
        );
    }

    public function destroy(int $id, int $reviewId, Request $request): JsonResponse
    {
        $reviews = $this->service->deleteReview($id, $reviewId, $request->user()->id);

        return $this->success(
            ['reviews' => $reviews],
            'Review deleted successfully'
        );
    }
}
