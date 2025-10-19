<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'cartable_type',
        'cartable_id',
        'quantity',
        'price',
        'options',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'options' => 'json',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function cartable(): MorphTo
    {
        return $this->morphTo();
    }

    public function subtotal(): float
    {
        return (float) ($this->price * $this->quantity);
    }

    /**
     * Get the tree instance if this is a tree item
     */
    public function treeInstance(): BelongsTo
    {
        return $this->belongsTo(TreeInstance::class, 'cartable_id')
            ->where('cartable_type', TreeInstance::class);
    }

    /**
     * Get the product variant if this is a product item
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'cartable_id')
            ->where('cartable_type', ProductVariant::class);
    }

    /**
     * Get the campaign if this is a campaign item
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'cartable_id')
            ->where('cartable_type', Campaign::class);
    }

    /**
     * Check if this is a tree item (sponsor/adopt)
     */
    public function isTree(): bool
    {
        return $this->cartable_type === TreeInstance::class;
    }

    /**
     * Check if this is a product item
     */
    public function isProduct(): bool
    {
        return in_array($this->cartable_type, [Product::class, ProductVariant::class]);
    }

    /**
     * Check if this is a campaign item (feed/protect/plant)
     */
    public function isCampaign(): bool
    {
        return $this->cartable_type === Campaign::class;
    }

    /**
     * Get item details for display
     */
    public function getItemDetails(): array
    {
        if ($this->isTree()) {
            $tree = $this->cartable;
            $planPrice = $this->options['tree_plan_price'] ?? null;

            return [
                'type' => 'tree',
                'name' => $tree->tree->name ?? 'Tree',
                'sku' => $tree->sku,
                'image' => $tree->tree->getFirstMediaUrl('thumbnails') ?? null,
                'plan_name' => $planPrice['plan_name'] ?? null,
                'plan_type' => $planPrice['plan_type'] ?? null,
                'duration' => $planPrice['duration_display'] ?? null,
            ];
        }

        if ($this->isProduct()) {
            $product = $this->cartable;

            return [
                'type' => 'product',
                'name' => $product->product->name ?? $product->name ?? 'Product',
                'sku' => $product->sku ?? null,
                'image' => $product->getFirstMediaUrl('images') ?? null,
                'variant' => $this->options['variant'] ?? null,
            ];
        }

        if ($this->isCampaign()) {
            $campaign = $this->cartable;

            return [
                'type' => 'campaign',
                'name' => $campaign->name ?? 'Campaign',
                'campaign_type' => $campaign->type->label() ?? null,
                'image' => $campaign->getFirstMediaUrl('thumbnails') ?? null,
                'location' => $campaign->location->name ?? null,
            ];
        }

        return [
            'type' => 'unknown',
            'name' => 'Item',
        ];
    }
}
