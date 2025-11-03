<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get images from inventory (global product images)
        $thumbnailUrl = null;
        $imageUrls = [];

        if ($this->inventory) {
            // Get inventory thumbnail for product listing
            $thumbnail = $this->inventory->getFirstMedia("thumbnail");
            $thumbnailUrl = $thumbnail?->getFullUrl();

            // Get inventory-level images (global product images)
            $inventoryImages = $this->inventory->getMedia("images");
            if ($inventoryImages->count() > 0) {
                $imageUrls = $inventoryImages->map(fn($media) => $media->getFullUrl())->toArray();
            }
        }

        // Get stock quantity and status from first variant if available
        $stockQuantity = 0;
        $isInStock = false;
        $price = 0;
        $discountPrice = null;

        if ($this->inventory && $this->inventory->productVariants->count() > 0) {
            $firstVariant = $this->inventory->productVariants->first();
            $stockQuantity = $firstVariant->stock_quantity ?? 0;
            $isInStock = $firstVariant->is_instock ?? false;
            $price = $firstVariant->base_price ?? 0;
            $discountPrice = $firstVariant->discount_price ?? null;
        }

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
            "inventory" => [
                "id" => $this->inventory->id ?? null,
                "stock_quantity" => $stockQuantity,
                "is_instock" => $isInStock,
                "has_variants" => $this->inventory && $this->inventory->productVariants->count() > 0,
            ],
            "variants" => ProductVariantResource::collection(
                $this->inventory->productVariants ?? collect(),
            ),
            "default_variant" => $this->inventory && $this->inventory->productVariants->count() > 0
                ? new ProductVariantResource($this->inventory->productVariants->first())
                : null,
            "formatted_price" => $price ? "₹" . number_format($price, 2) : null,
            "has_variants" => $this->inventory && $this->inventory->productVariants->count() > 0,
            "variant_options" => $this->inventory && $this->inventory->productVariants->count() > 0 ? [
                "colors" => $this->inventory->productVariants->filter(fn($v) => $v->variant && $v->variant->color)
                    ->map(fn($v) => $v->variant->color)
                    ->unique('id')
                    ->values()
                    ->map(fn($color) => [
                        'id' => $color->id,
                        'name' => $color->name,
                        'code' => $color->code,
                    ]),
                "sizes" => $this->inventory->productVariants->filter(fn($v) => $v->variant && $v->variant->size)
                    ->map(fn($v) => $v->variant->size)
                    ->unique('id')
                    ->values()
                    ->map(fn($size) => [
                        'id' => $size->id,
                        'name' => $size->name,
                    ]),
                "planters" => $this->inventory->productVariants->filter(fn($v) => $v->variant && $v->variant->planter)
                    ->map(fn($v) => $v->variant->planter)
                    ->unique('id')
                    ->values()
                    ->map(fn($planter) => [
                        'id' => $planter->id,
                        'name' => $planter->name,
                        'image_url' => $planter->getFirstMediaUrl('images') ?? '',
                    ]),
            ] : null,
        ];
    }
}
