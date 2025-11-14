<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgeUnitEnum;
use App\Models\Scopes\ActiveScope;
use App\Traits\GeneratesSku;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ScopedBy([ActiveScope::class])]
final class Tree extends Model implements HasMedia
{
    use GeneratesSku;
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

    public function instances(): HasMany
    {
        return $this->hasMany(TreeInstance::class);
    }

    public function planPrices(): HasMany
    {
        return $this->hasMany(TreePlanPrice::class);
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
