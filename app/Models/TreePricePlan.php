<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgeUnitEnum;
use App\Enums\TreeTypeEnum;
use App\Traits\GeneratesSku;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TreePricePlan extends Model
{
    use GeneratesSku;

    protected $casts = [
        'type' => TreeTypeEnum::class,
        'price' => 'decimal:2',
        'duration' => 'integer',
        'duration_type' => AgeUnitEnum::class,
        'features' => 'json',
        'is_active' => 'boolean',
    ];

    public function planPrices(): HasMany
    {
        return $this->hasMany(TreePlanPrice::class);
    }

    #[Scope]
    protected function active($query)
    {
        return $query->where('is_active', true);
    }

    private static function skuPrefix($model = null): string
    {
        return $model && $model->type->label() ? mb_strtoupper(mb_substr((string) $model->type->label(), 0, 3)).'-' : 'TPP-';
    }

    private static function skuPadding(): int
    {
        return 4;
    }
}
