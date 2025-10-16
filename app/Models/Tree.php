<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgeUnitEnum;
use App\Traits\GeneratesSku;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Tree extends Model implements HasMedia
{
    use InteractsWithMedia, GeneratesSku;

    protected $casts = [
        'is_active' => 'boolean',
        'age_unit' => AgeUnitEnum::class,
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnails')->singleFile();
        $this->addMediaCollection('images');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(TreeInstance::class);
    }

    public function planPrices(): HasMany
    {
        return $this->hasMany(TreePlanPrice::class);
    }

    #[Scope]
    protected function active($query)
    {
        return $query->where('is_active', true);
    }

    private static function skuPrefix(): string
    {
        return 'TREE-';
    }

    private static function skuPadding(): int
    {
        return 4;
    }
}
