<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum TreeConditionEnum: string
{
    use HasLabelOptions;

    case EXCELLENT = 'excellent';

    case GOOD = 'good';

    case AVERAGE = 'average';

    case POOR = 'poor';

    case DEAD = 'dead';

    public function label(): string
    {
        return match ($this) {
            self::EXCELLENT => 'Excellent',
            self::GOOD => 'Good',
            self::AVERAGE => 'Average',
            self::POOR => 'Poor',
            self::DEAD => 'Dead'
        };
    }
}
