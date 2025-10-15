<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgeUnitEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Tree extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $casts = [
        'is_active' => 'boolean',
        'age_unit' => AgeUnitEnum::class,
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnails')->singleFile();
        $this->addMediaCollection('images');
    }

    public function treePrices(): HasMany
    {
        return $this->hasMany(TreePrice::class);
    }

    public function treeLocations(): HasMany
    {
        return $this->hasMany(TreeLocation::class);
    }
}
