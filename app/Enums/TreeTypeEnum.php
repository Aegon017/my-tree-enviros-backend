<?php

declare(strict_types=1);

namespace App\Enums;

enum TreeTypeEnum: string
{
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
