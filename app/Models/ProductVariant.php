<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\GeneratesSku;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class ProductVariant extends Model implements HasMedia
{
    use GeneratesSku;
    use InteractsWithMedia;

    protected $fillable = ['inventory_id', 'variant_id', 'sku', 'base_price', 'discount_price', 'stock_quantity', 'is_instock'];

    protected $appends = ['price'];

    protected $casts = [
        'inventory_id' => 'integer',
        'variant_id' => 'integer',
        'base_price' => 'float',
        'discount_price' => 'float',
        'stock_quantity' => 'integer',
        'is_instock' => 'boolean',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function getPriceAttribute(): float
    {
        return $this->discount_price ?? $this->base_price ?? 0;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    private static function skuPrefix(): string
    {
        return 'PRODVAR-';
    }

    private static function skuPadding(): int
    {
        return 4;
    }
}
