<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum AgeUnitEnum: string
{
    use HasLabelOptions;

    case DAY = 'day';

    case MONTH = 'month';

    case YEAR = 'year';

    public function label(): string
    {
        return match ($this) {
            self::DAY => 'Day',
            self::MONTH => 'Month',
            self::YEAR => 'Year',
        };
    }
}
