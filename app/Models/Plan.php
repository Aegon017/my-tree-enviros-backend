<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DurationUnitEnum;
use App\Enums\PlanTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Plan extends Model
{
    protected $casts = [
        'type' => PlanTypeEnum::class,
        'duration_unit' => DurationUnitEnum::class,
    ];

    public function planPrices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }

    public function sponsorRecords(): HasMany
    {
        return $this->hasMany(SponsorRecord::class);
    }

    public function adoptRecords(): HasMany
    {
        return $this->hasMany(AdoptRecord::class);
    }
}
