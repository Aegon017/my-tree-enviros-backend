<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChargeModeEnum;
use App\Enums\ChargeTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Charge extends Model
{
    protected $casts = [
        'type' => ChargeTypeEnum::class,
        'mode' => ChargeModeEnum::class,
        'value' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function orderCharges(): HasMany
    {
        return $this->hasMany(OrderCharge::class);
    }
}
