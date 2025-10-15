<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductCategory extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['name', 'slug'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }


    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
