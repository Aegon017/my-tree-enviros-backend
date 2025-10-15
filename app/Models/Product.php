<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'product_category_id',
        'name',
        'slug',
        'botanical_name',
        'nick_name',
        'short_description',
        'description',
        'base_price',
        'discount_price',
        'is_active',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')->singleFile();
        $this->addMediaCollection('image')->singleFile();
        $this->addMediaCollection('sub_images');
    }







    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }
}
