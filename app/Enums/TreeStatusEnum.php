<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum TreeStatusEnum: string
{
    use HasLabelOptions;

    case SPONSORED = 'sponsored';
    case WAITING_ADOPTION = 'waiting_adoption';
    case ADOPTED = 'adopted';

    public function label(): string
    {
        return match ($this) {
            self::SPONSORED => 'Sponsored',
            self::WAITING_ADOPTION => 'Waiting Adoption',
            self::ADOPTED => 'Adopted'
        };
    }
}
