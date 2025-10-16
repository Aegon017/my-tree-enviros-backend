<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgeUnitEnum;
use App\Enums\TreeTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TreePricePlan extends Model
{
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

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function active($query)
    {
        return $query->where('is_active', true);
    }
}
