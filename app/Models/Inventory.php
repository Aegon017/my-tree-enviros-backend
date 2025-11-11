<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Inventory extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')->singleFile();
    }
}
