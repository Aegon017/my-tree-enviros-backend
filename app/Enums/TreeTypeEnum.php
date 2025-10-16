<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum TreeTypeEnum: string
{
    use HasLabelOptions;

    case SPONSORSHIP = 'sponsorship';
    case ADOPTION = 'adoption';

    public function label(): string
    {
        return match ($this) {
            self::SPONSORSHIP => 'Sponsorship',
            self::ADOPTION => 'Adoption'
        };
    }
}
