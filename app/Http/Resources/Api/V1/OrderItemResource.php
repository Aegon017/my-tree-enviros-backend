<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'quantity' => $this->quantity,

            // Use snapshot data (preferred) with fallback to relationships
            'name' => $this->item_name ?? $this->getFallbackName(),
            'unit_price' => $this->unit_price ?? $this->amount,
            'amount' => $this->amount,
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,

            // Snapshot image from Spatie Media Library
            'image_url' => $this->getFirstMediaUrl('snapshot_image') ?: $this->getFallbackImageUrl(),

            // Detailed snapshot data based on type
            'details' => $this->getSnapshotDetails(),

            // Dedication (if exists)
            'dedication' => $this->whenLoaded('dedication', fn() => [
                'name' => $this->dedication->name,
                'message' => $this->dedication->message,
                'occasion' => $this->dedication->occasion,
            ]),
        ];
    }

    /**
     * Get fallback name from relationships if snapshot not available
     */
    private function getFallbackName(): string
    {
        return match ($this->type) {
            'product' => $this->productVariant?->inventory?->product?->name ?? 'Product',
            'sponsor' => ($this->tree?->name ?? 'Tree') . ' Sponsorship',
            'adopt' => ($this->tree?->name ?? 'Tree') . ' Adoption',
            'campaign' => $this->campaign?->name ?? 'Campaign Contribution',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get fallback image URL from relationships if snapshot image not available
     */
    private function getFallbackImageUrl(): ?string
    {
        return match ($this->type) {
            'product' => $this->productVariant?->getFirstMediaUrl('images'),
            'sponsor', 'adopt' => $this->tree?->getFirstMediaUrl('images'),
            'campaign' => $this->campaign?->getFirstMediaUrl('images'),
            default => null,
        };
    }

    /**
     * Get detailed snapshot data based on item type
     */
    private function getSnapshotDetails(): ?array
    {
        if (!$this->item_snapshot) {
            return $this->getLegacyDetails();
        }

        $snapshot = $this->item_snapshot;

        return match ($snapshot['type'] ?? $this->type) {
            'product' => [
                'product_name' => $snapshot['product']['name'] ?? null,
                'botanical_name' => $snapshot['product']['botanical_name'] ?? null,
                'description' => $snapshot['product']['description'] ?? null,
                'variant' => [
                    'sku' => $snapshot['product']['variant']['sku'] ?? null,
                    'variant_name' => $snapshot['product']['variant']['variant_name'] ?? null,
                    'original_price' => $snapshot['product']['variant']['original_price'] ?? null,
                    'selling_price' => $snapshot['product']['variant']['selling_price'] ?? null,
                ],
            ],
            'sponsor' => [
                'tree_name' => $snapshot['sponsor']['tree']['name'] ?? null,
                'tree_description' => $snapshot['sponsor']['tree']['description'] ?? null,
                'plan_type' => $snapshot['sponsor']['plan']['type'] ?? null,
                'plan_duration' => $snapshot['sponsor']['plan']['duration'] ?? null,
                'plan_duration_unit' => $snapshot['sponsor']['plan']['duration_unit'] ?? null,
                'price' => $snapshot['sponsor']['plan_price']['price'] ?? null,
                'location' => $snapshot['sponsor']['plan_price']['location_name'] ?? null,
                'quantity' => $snapshot['sponsor']['quantity'] ?? null,
            ],
            'adopt' => [
                'tree_name' => $snapshot['adopt']['tree_instance']['tree_name'] ?? null,
                'age' => $snapshot['adopt']['tree_instance']['age'] ?? null,
                'age_unit' => $snapshot['adopt']['tree_instance']['age_unit'] ?? null,
                'location' => $snapshot['adopt']['tree_instance']['location_name'] ?? null,
                'plan_type' => $snapshot['adopt']['plan']['type'] ?? null,
                'plan_duration' => $snapshot['adopt']['plan']['duration'] ?? null,
                'price' => $snapshot['adopt']['plan_price']['price'] ?? null,
            ],
            'campaign' => [
                'campaign_name' => $snapshot['campaign']['name'] ?? null,
                'description' => $snapshot['campaign']['description'] ?? null,
                'target_amount' => $snapshot['campaign']['target_amount'] ?? null,
                'location' => $snapshot['campaign']['location_name'] ?? null,
                'start_date' => $snapshot['campaign']['start_date'] ?? null,
                'end_date' => $snapshot['campaign']['end_date'] ?? null,
            ],
            default => null,
        };
    }

    /**
     * Get legacy details from relationships (for old orders without snapshots)
     */
    private function getLegacyDetails(): ?array
    {
        return match ($this->type) {
            'product' => $this->productVariant ? [
                'product_name' => $this->productVariant->inventory?->product?->name,
                'variant_name' => $this->productVariant->variant?->name,
                'sku' => $this->productVariant->sku,
            ] : null,
            'sponsor', 'adopt' => $this->tree ? [
                'tree_name' => $this->tree->name,
                'tree_description' => $this->tree->description,
            ] : null,
            'campaign' => $this->campaign ? [
                'campaign_name' => $this->campaign->name,
                'description' => $this->campaign->description,
            ] : null,
            default => null,
        };
    }
}
