<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductVariant extends Model
{
    protected $fillable = ['inventory_id', 'variant_id', 'sku', 'base_price', 'discount_price', 'stock_quantity', 'is_instock'];


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
}
