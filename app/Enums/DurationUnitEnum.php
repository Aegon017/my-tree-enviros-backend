<?php

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum DurationUnitEnum: string
{
    use HasLabelOptions;

    case MONTH = 'month';
    case YEAR = 'year';

    public function label(): string
    {
        return match ($this) {
            self::MONTH => 'Month',
            self::YEAR => 'Year'
        };
    }
}
