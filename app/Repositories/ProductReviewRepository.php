<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class ProductReviewRepository
{
    public function getProductReviews(int $productId): Collection
    {
        return ProductReview::query()
            ->where('product_id', $productId)
            ->with('user:id,name,email')
            ->latest()
            ->get();
    }

    public function create(int $productId, int $userId, array $data): ProductReview
    {
        return ProductReview::create([
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);
    }

    public function update(ProductReview $review, array $data): ProductReview
    {
        $review->update([
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return $review->fresh(['user:id,name,email']);
    }

    public function delete(ProductReview $review): void
    {
        $review->delete();
    }

    public function findById(int $reviewId): ?ProductReview
    {
        return ProductReview::find($reviewId);
    }


    public function userHasReviewed(int $productId, int $userId): bool
    {
        return ProductReview::where('product_id', $productId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function userHasPurchasedProduct(int $productId, int $userId): bool
    {
        return DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('inventories', 'product_variants.inventory_id', '=', 'inventories.id')
            ->where('orders.user_id', $userId)
            ->where('orders.status', 'success')
            ->where('inventories.product_id', $productId)
            ->exists();
    }
}
