<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class WishlistItem extends Model
{
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
