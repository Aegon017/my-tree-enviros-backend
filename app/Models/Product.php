<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        "product_category_id",
        "name",
        "slug",
        "botanical_name",
        "nick_name",
        "base_price",
        "discount_price",
        "short_description",
        "description",
        "is_active",
    ];

    protected $casts = [
        "product_category_id" => "integer",
        "base_price" => "float",
        "discount_price" => "float",
        "is_active" => "boolean",
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection("thumbnails")
            ->singleFile()
            ->registerMediaConversions(function () {
                $this->addMediaConversion("thumb")
                    ->width(150)
                    ->height(150)
                    ->sharpen(10)
                    ->nonQueued();
            });

        $this->addMediaCollection("images")
            ->registerMediaConversions(function () {
                $this->addMediaConversion("thumb")
                    ->width(150)
                    ->height(150)
                    ->sharpen(10)
                    ->nonQueued();
            });
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }
}
