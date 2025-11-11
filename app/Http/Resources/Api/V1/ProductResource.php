<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $inventory = $this->inventory;
        $thumbnailUrl = $inventory?->getFirstMedia('thumbnail')?->getFullUrl() ?? '';
        $imageUrls = $inventory?->getMedia('images')->map(fn($m) => $m->getFullUrl())->toArray() ?? [];

        $variants = $inventory?->productVariants ?? collect();
        $firstVariant = $variants->first();
        $stockQuantity = (int) ($firstVariant->stock_quantity ?? 0);
        $isInStock = (bool) ($firstVariant->is_instock ?? false);

        $nonBaseVariants = $variants->filter(
            fn($v) => $v->variant && ($v->variant->color || $v->variant->size || $v->variant->planter)
        );

        $hasVariants = $nonBaseVariants->isNotEmpty();
        $defaultVariant = $hasVariants ? $nonBaseVariants->first() : null;

        $variantOptions = $hasVariants ? [
            'colors' => $nonBaseVariants->pluck('variant.color')->filter()->unique('id')->values()->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
            ]),
            'sizes' => $nonBaseVariants->pluck('variant.size')->filter()->unique('id')->values()->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
            ]),
            'planters' => $nonBaseVariants->pluck('variant.planter')->filter()->unique('id')->values()->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'image_url' => $p->getFirstMediaUrl('images') ?? '',
            ]),
        ] : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'nick_name' => $this->nick_name ?? $this->name,
            'botanical_name' => $this->botanical_name ?? '',
            'slug' => $this->slug,
            'sku' => $this->sku ?? 'N/A',
            'type' => $this->type ?? 1,
            'status' => $this->is_active ? 1 : 0,
            'trash' => 0,
            'category_id' => $this->product_category_id ?? null,

            'category' => [
                'id' => $this->productCategory->id ?? null,
                'name' => $this->productCategory->name ?? 'Uncategorized',
                'slug' => $this->productCategory->slug ?? 'uncategorized',
                'icon' => '',
                'status' => 1,
            ],

            'description' => $this->description ?? '',
            'short_description' => $this->short_description ?? '',

            'selling_price' => $this->selling_price,
            'original_price' => $this->original_price,

            'quantity' => $stockQuantity,
            'thumbnail_url' => $thumbnailUrl,
            'image_urls' => $imageUrls,
            'reviews' => [],
            'in_wishlist' => $this->in_wishlist,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'rating' => 0,
            'review_count' => 0,
            'is_active' => $this->is_active,

            'inventory' => [
                'id' => $inventory->id ?? null,
                'stock_quantity' => $stockQuantity,
                'is_instock' => $isInStock,
                'has_variants' => $hasVariants,
            ],

            'variants' => ProductVariantResource::collection($variants),
            'default_variant' => $defaultVariant ? new ProductVariantResource($defaultVariant) : null,
            'has_variants' => $hasVariants,
            'default_variant_id' => $defaultVariant?->id,
            'variant_options' => $variantOptions,
        ];
    }
}