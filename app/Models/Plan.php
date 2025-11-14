<?php

namespace App\Models;

use App\Enums\DurationUnitEnum;
use App\Enums\PlanTypeEnum;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $casts = [
        'type' => PlanTypeEnum::class,
        'duration_unit' => DurationUnitEnum::class,
    ];

    public function planPrices()
    {
        return $this->hasMany(PlanPrice::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
