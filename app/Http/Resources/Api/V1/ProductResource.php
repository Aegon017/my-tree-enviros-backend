<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Map media to local media route URLs so private storage can be streamed
        $mainImage = $this->getFirstMedia("images");
        $mainImageUrl = null;
        if ($mainImage) {
            $mainImageUrl = $mainImage->getFullUrl();
        }

        $thumbnail =
            $this->getFirstMedia("thumbnails") ??
            $this->getFirstMedia("images");
        $thumbnailUrl = null;
        if ($thumbnail) {
            $thumbnailUrl = $thumbnail->getFullUrl();
        }

        $imageUrls = $this->getMedia("images")
            ->map(fn($media) => $media->getFullUrl())
            ->toArray();

        // Calculate price from inventory if exists
        $price = $this->base_price ?? 0;
        $discountPrice = $this->discount_price ?? null;

        // Get stock quantity and status
        $stockQuantity = $this->inventory?->stock_quantity ?? 0;
        $isInStock = $this->inventory?->is_instock ?? false;

        return [
            "id" => $this->id,
            "name" => $this->name,
            "nick_name" => $this->nick_name ?? $this->name,
            "botanical_name" => $this->botanical_name ?? "",
            "slug" => $this->slug,
            "sku" => $this->sku ?? "N/A",
            "type" => $this->type ?? 1,
            "status" => $this->is_active ? 1 : 0,
            "trash" => 0,
            "category_id" => $this->product_category_id ?? null,
            "category" => [
                "id" => $this->productCategory->id ?? null,
                "name" => $this->productCategory->name ?? "Uncategorized",
                "slug" => $this->productCategory->slug ?? "uncategorized",
                "icon" => "",
                "status" => 1,
            ],
            "description" => $this->description ?? "",
            "short_description" => $this->short_description ?? "",
            "price" => (float) $price,
            "discount_price" => $discountPrice ? (float) $discountPrice : null,
            "quantity" => $stockQuantity,
            "thumbnail_url" => $thumbnailUrl ?? "",
            "main_image_url" => $mainImageUrl ?? "",
            "image_urls" => $imageUrls,
            "reviews" => [], // Reviews will be added later if needed
            "wishlist_tag" => $this->in_wishlist ?? false,
            "created_at" =>
                $this->created_at?->toISOString() ?? now()->toISOString(),
            "updated_at" =>
                $this->updated_at?->toISOString() ?? now()->toISOString(),
            "created_by" => 0,
            "updated_by" => 0,
            "rating" => 0,
            "review_count" => 0,
            // Additional fields from new structure
            "is_active" => $this->is_active,
            "inventory" => $this->when(
                $this->relationLoaded("inventory"),
                function () {
                    return [
                        "id" => $this->inventory->id ?? null,
                        "stock_quantity" =>
                            $this->inventory->stock_quantity ?? 0,
                        "is_instock" => $this->inventory->is_instock ?? false,
                        "has_variants" =>
                            $this->inventory?->productVariants()->count() > 0,
                    ];
                },
            ),
            "variants" => ProductVariantResource::collection(
                $this->whenLoaded("inventory.productVariants"),
            ),
            "formatted_price" => $price ? "₹" . number_format($price, 2) : null,
        ];
    }
}
