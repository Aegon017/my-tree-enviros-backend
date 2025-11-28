<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum ChargeModeEnum: string
{
    use HasLabelOptions;

    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'Fixed',
            self::PERCENTAGE => 'Percentage',
        };
    }
}
