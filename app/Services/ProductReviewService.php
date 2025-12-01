<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\Api\V1\ProductReviewResource;
use App\Models\ProductReview;
use App\Repositories\ProductReviewRepository;
use Illuminate\Http\Exceptions\HttpResponseException;

final readonly class ProductReviewService
{
    public function __construct(private ProductReviewRepository $repo) {}

    public function getProductReviews(int $productId): array
    {
        $reviews = $this->repo->getProductReviews($productId);

        return ProductReviewResource::collection($reviews)->resolve();
    }

    public function createReview(int $productId, int $userId, array $data): array
    {
        if ($this->repo->userHasReviewed($productId, $userId)) {
            throw new HttpResponseException(
                response()->json(['message' => 'You have already reviewed this product'], 422)
            );
        }

        if (!$this->repo->userHasPurchasedProduct($productId, $userId)) {
            throw new HttpResponseException(
                response()->json(['message' => 'You can only review products you have purchased'], 403)
            );
        }

        $this->repo->create($productId, $userId, $data);

        return $this->getProductReviews($productId);
    }

    public function updateReview(int $productId, int $reviewId, int $userId, array $data): array
    {
        $review = $this->repo->findById($reviewId);

        if (!$review) {
            throw new HttpResponseException(
                response()->json(['message' => 'Review not found'], 404)
            );
        }

        if ($review->user_id !== $userId) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized'], 403)
            );
        }

        $this->repo->update($review, $data);

        return $this->getProductReviews($productId);
    }

    public function deleteReview(int $productId, int $reviewId, int $userId): array
    {
        $review = $this->repo->findById($reviewId);

        if (!$review) {
            throw new HttpResponseException(
                response()->json(['message' => 'Review not found'], 404)
            );
        }

        if ($review->user_id !== $userId) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized'], 403)
            );
        }

        $this->repo->delete($review);

        return $this->getProductReviews($productId);
    }
}
