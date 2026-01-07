<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Campaign;
use App\Models\PlanPrice;
use App\Models\ProductVariant;
use App\Models\ShippingAddress;
use App\Models\TreeInstance;

final readonly class OrderSnapshotService
{
    /**
     * Create a snapshot for a shipping address
     */
    public function createShippingAddressSnapshot(?ShippingAddress $address): ?array
    {
        if (! $address) {
            return null;
        }

        return [
            'name' => $address->name,
            'phone' => $address->phone,
            'address' => $address->address,
            'area' => $address->area,
            'city' => $address->city,
            'postal_code' => $address->postal_code,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'post_office_name' => $address->post_office_name,
            'post_office_branch_type' => $address->post_office_branch_type,
        ];
    }

    /**
     * Create a snapshot for an order item based on its type
     */
    public function createItemSnapshot(array $item): array
    {
        return match ($item['type']) {
            'product' => $this->createProductSnapshot($item),
            'sponsor' => $this->createSponsorSnapshot($item),
            'adopt' => $this->createAdoptSnapshot($item),
            'campaign' => $this->createCampaignSnapshot($item),
            default => ['type' => $item['type'], 'raw_data' => $item],
        };
    }

    /**
     * Extract the item name from the snapshot
     */
    public function extractItemName(array $snapshot): ?string
    {
        return match ($snapshot['type']) {
            'product' => $snapshot['product']['name'] ?? null,
            'sponsor' => ($snapshot['sponsor']['tree']['name'] ?? 'Tree') . ' - ' . ($snapshot['sponsor']['plan']['type'] ?? 'Plan'),
            'adopt' => $snapshot['adopt']['tree_instance']['tree_name'] ?? null,
            'campaign' => $snapshot['campaign']['name'] ?? null,
            default => null,
        };
    }

    /**
     * Extract the unit price from the snapshot
     */
    public function extractUnitPrice(array $snapshot): ?float
    {
        return match ($snapshot['type']) {
            'product' => $snapshot['product']['variant']['selling_price'] ?? $snapshot['product']['variant']['original_price'] ?? null,
            'sponsor' => $snapshot['sponsor']['plan_price']['price'] ?? null,
            'adopt' => $snapshot['adopt']['plan_price']['price'] ?? null,
            'campaign' => $snapshot['campaign']['amount'] ?? null,
            default => null,
        };
    }

    /**
     * Copy the snapshot image from source entity to OrderItem
     */
    public function copySnapshotImage(array $item, $orderItem): void
    {
        $sourceMedia = match ($item['type']) {
            'product' => ProductVariant::find($item['product_variant_id'])?->getFirstMedia('images'),
            'sponsor', 'adopt' => $this->getTreeMedia($item),
            'campaign' => Campaign::find($item['campaign_id'])?->getFirstMedia('images'),
            default => null,
        };

        if ($sourceMedia) {
            $sourceMedia->copy($orderItem, 'snapshot_image');
        }
    }

    /**
     * Get tree media for sponsor/adopt orders
     */
    private function getTreeMedia(array $item): ?\Spatie\MediaLibrary\MediaCollections\Models\Media
    {
        if ($item['type'] === 'sponsor' && isset($item['plan_price_id'])) {
            $planPrice = PlanPrice::with('tree')->find($item['plan_price_id']);
            return $planPrice?->tree?->getFirstMedia('images');
        }

        if ($item['type'] === 'adopt' && isset($item['tree_instance_id'])) {
            $treeInstance = TreeInstance::with('tree')->find($item['tree_instance_id']);
            return $treeInstance?->tree?->getFirstMedia('images');
        }

        return null;
    }

    /**
     * Create a product snapshot
     */
    private function createProductSnapshot(array $item): array
    {
        if (!isset($item['product_variant_id'])) {
            throw new \InvalidArgumentException('product_variant_id is required for product type orders');
        }

        $variant = ProductVariant::with(['inventory.product', 'variant'])
            ->findOrFail($item['product_variant_id']);

        $product = $variant->inventory->product;

        return [
            'type' => 'product',
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'botanical_name' => $product->botanical_name,
                'nick_name' => $product->nick_name,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'variant' => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'variant_name' => $variant->variant?->name,
                    'original_price' => (float) $variant->original_price,
                    'selling_price' => $variant->selling_price ? (float) $variant->selling_price : null,
                ],
            ],
        ];
    }

    /**
     * Create a sponsor snapshot
     */
    private function createSponsorSnapshot(array $item): array
    {
        if (!isset($item['plan_price_id'])) {
            throw new \InvalidArgumentException('plan_price_id is required for sponsor type orders');
        }

        $planPrice = PlanPrice::with(['plan', 'tree', 'location'])
            ->findOrFail($item['plan_price_id']);

        return [
            'type' => 'sponsor',
            'sponsor' => [
                'tree' => [
                    'id' => $planPrice->tree->id,
                    'name' => $planPrice->tree->name,
                    'slug' => $planPrice->tree->slug,
                    'description' => $planPrice->tree->description,
                    'default_age' => $planPrice->tree->default_age,
                    'age_unit' => $planPrice->tree->age_unit,
                ],
                'plan' => [
                    'id' => $planPrice->plan->id,
                    'type' => $planPrice->plan->type->value,
                    'duration' => $planPrice->plan->duration,
                    'duration_unit' => $planPrice->plan->duration_unit,
                ],
                'plan_price' => [
                    'price' => (float) $planPrice->price,
                    'location_id' => $planPrice->location_id,
                    'location_name' => $planPrice->location?->name,
                ],
                'quantity' => $item['sponsor_quantity'] ?? 1,
            ],
        ];
    }

    /**
     * Create an adopt snapshot
     */
    private function createAdoptSnapshot(array $item): array
    {
        if (!isset($item['tree_instance_id'])) {
            throw new \InvalidArgumentException('tree_instance_id is required for adopt type orders');
        }

        if (!isset($item['plan_price_id'])) {
            throw new \InvalidArgumentException('plan_price_id is required for adopt type orders');
        }

        $treeInstance = TreeInstance::with(['tree', 'location'])
            ->findOrFail($item['tree_instance_id']);

        $planPrice = PlanPrice::with(['plan', 'tree', 'location'])
            ->findOrFail($item['plan_price_id']);

        return [
            'type' => 'adopt',
            'adopt' => [
                'tree_instance' => [
                    'id' => $treeInstance->id,
                    'tree_id' => $treeInstance->tree->id,
                    'tree_name' => $treeInstance->tree->name,
                    'tree_slug' => $treeInstance->tree->slug,
                    'age' => $treeInstance->age,
                    'age_unit' => $treeInstance->age_unit,
                    'location_name' => $treeInstance->location?->name,
                ],
                'plan' => [
                    'id' => $planPrice->plan->id,
                    'type' => $planPrice->plan->type->value,
                    'duration' => $planPrice->plan->duration,
                    'duration_unit' => $planPrice->plan->duration_unit,
                ],
                'plan_price' => [
                    'price' => (float) $planPrice->price,
                    'location_id' => $planPrice->location_id,
                    'location_name' => $planPrice->location?->name,
                ],
            ],
        ];
    }

    /**
     * Create a campaign snapshot
     */
    private function createCampaignSnapshot(array $item): array
    {
        if (!isset($item['campaign_id'])) {
            throw new \InvalidArgumentException('campaign_id is required for campaign type orders');
        }

        $campaign = Campaign::with('location')
            ->findOrFail($item['campaign_id']);

        return [
            'type' => 'campaign',
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'slug' => $campaign->slug,
                'description' => $campaign->description,
                'target_amount' => (float) $campaign->target_amount,
                'location_name' => $campaign->location?->name,
                'start_date' => $campaign->start_date?->toDateString(),
                'end_date' => $campaign->end_date?->toDateString(),
                'amount' => $item['amount'] ?? null,
            ],
        ];
    }
}
