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
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function dedication()
    {
        return $this->morphOne(TreeDedication::class, 'dedicatable');
    }

    public function treeInstance(): BelongsTo
    {
        return $this->belongsTo(TreeInstance::class);
    }

    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function isTree(): bool
    {
        return in_array($this->type, ['sponsor', 'adopt', 'tree']);
    }

    public function isProduct(): bool
    {
        return $this->type === 'product';
    }

    public function isCampaign(): bool
    {
        return $this->type === 'campaign';
    }

    /**
     * Get item details for display
     */
    public function getItemDetails(): array
    {
        // For tree items
        if ($this->isTree()) {
            $tree = $this->tree;
            $plan = $this->plan;

            return [
                'type' => 'tree',
                'item_type' => 'tree',
                'name' => $tree->name ?? 'Tree',
                'image' => $tree->image_url ?? null, // Assuming image_url exists or use media
                'plan_name' => $plan->name ?? null,
                'plan_type' => $this->type,
                'location' => $this->treeInstance->location->name ?? null,
            ];
        }

        // For product items
        if ($this->isProduct()) {
            $variant = $this->productVariant;
            $product = $variant->product ?? null;

            return [
                'type' => 'product',
                'item_type' => 'product',
                'name' => $product->name ?? 'Product',
                'image' => $product->main_image_url ?? null,
                'variant' => $variant ? [
                    'color' => $variant->color,
                    'size' => $variant->size,
                ] : null,
            ];
        }

        // For campaign items
        if ($this->isCampaign()) {
            return [
                'type' => 'campaign',
                'item_type' => 'campaign',
                'name' => 'Campaign Donation',
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
        return $this->type ?? 'unknown';
    }
}
