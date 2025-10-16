<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ProductVariant extends Model
{
    protected $fillable = ['inventory_id', 'sku', 'color', 'size'];


    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
