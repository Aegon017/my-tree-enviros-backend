<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgeUnitEnum;
use App\Models\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ScopedBy([ActiveScope::class])]
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

    public function treeInstances(): HasMany
    {
        return $this->hasMany(TreeInstance::class);
    }

    public function planPrices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }
}
