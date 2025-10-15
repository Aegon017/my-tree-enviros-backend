<?php

namespace App\Models;

use App\Enums\AgeUnitEnum;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Tree extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $casts = [
        'is_active' => 'boolean',
        'age_unit' => AgeUnitEnum::class
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnails')->singleFile();
        $this->addMediaCollection('images');
    }
}
