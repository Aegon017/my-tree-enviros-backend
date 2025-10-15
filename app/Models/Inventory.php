<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'product_id',
        'stock_quantity',
        'is_instock',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
