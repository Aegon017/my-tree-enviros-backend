<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Inventory extends Model
{
    protected $fillable = [
        'product_id',
    ];

    protected $casts = [
        'product_id' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
