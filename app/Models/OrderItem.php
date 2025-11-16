<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class OrderItem extends Model
{
    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'options' => 'json',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_renewal' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderable(): MorphTo
    {
        return $this->morphTo();
    }

    public function dedication()
    {
        return $this->morphOne(TreeDedication::class, 'dedicatable');
    }

    /**
     * Legacy relationship - for tree items
     */
    public function treeInstance(): BelongsTo
    {
        return $this->belongsTo(TreeInstance::class);
    }

    /**
     * Legacy relationship - for tree plan pricing
     */
    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
    }

    /**
     * Get product variant if this is a product order
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'orderable_id')
            ->where('orderable_type', ProductVariant::class);
    }

    /**
     * Get product if this is a product order
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'orderable_id')
            ->where('orderable_type', Product::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'orderable_id')
            ->where('orderable_type', Campaign::class);
    }

    /**
     * Get item details for display
     */
    public function getItemDetails(): array
    {
        // For tree items (backward compatibility)
        if ($this->isTree()) {
            $tree = $this->treeInstance ?? $this->orderable;
            $planPrice = $this->treePlanPrice;

            return [
                'type' => 'tree',
                'item_type' => 'tree',
                'name' => $tree->tree->name ?? 'Tree',
                'sku' => $tree->sku,
                'image' => $tree->tree->getFirstMediaUrl('thumbnails') ?? null,
                'plan_name' => $planPrice?->plan->name ?? $this->options['plan_name'] ?? null,
                'plan_type' => $planPrice?->plan->type->value ?? $this->options['plan_type'] ?? null,
                'duration' => $planPrice?->plan->duration_display ?? $this->options['duration_display'] ?? null,
                'location' => $tree->location->name ?? null,
            ];
        }

        // For product items
        if ($this->isProduct()) {
            $product = $this->orderable;

            return [
                'type' => 'product',
                'item_type' => 'product',
                'name' => $product->product->name ?? $product->name ?? 'Product',
                'sku' => $product->sku ?? null,
                'image' => $product->product->getFirstMediaUrl('images') ?? null,
                'variant' => $this->options['variant'] ?? null,
                'color' => $product->color ?? null,
                'size' => $product->size ?? null,
            ];
        }

        // For campaign items
        if ($this->isCampaign()) {
            $campaign = $this->orderable;

            return [
                'type' => 'campaign',
                'item_type' => 'campaign',
                'name' => $campaign->name ?? 'Campaign',
                'campaign_type' => $campaign->type->label() ?? null,
                'image' => $campaign->getFirstMediaUrl('thumbnails') ?? null,
                'location' => $campaign->location->name ?? null,
                'description' => $campaign->description ?? null,
            ];
        }

        return [
            'type' => 'unknown',
            'item_type' => 'unknown',
            'name' => 'Item',
        ];
    }

    /**
     * Get the order type based on item type
     */
    public function getOrderType(): string
    {
        if ($this->isTree()) {
            $planType = $this->treePlanPrice?->plan->type->value ?? $this->options['plan_type'] ?? null;

            if ($planType === 'sponsorship') {
                return 'sponsor';
            }

            if ($planType === 'adoption') {
                return 'adopt';
            }
        }

        if ($this->isProduct()) {
            return 'product';
        }

        if ($this->isCampaign()) {
            return 'campaign';
        }

        return 'unknown';
    }
}
