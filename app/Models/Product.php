<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Product extends Model
{

    protected $fillable = [
        "product_category_id",
        "name",
        "slug",
        "botanical_name",
        "nick_name",
        "short_description",
        "description",
        "is_active",
    ];

    protected $casts = [
        "product_category_id" => "integer",
        "is_active" => "boolean",
    ];



    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }
}
