<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum TreeStatusEnum: string
{
    use HasLabelOptions;

    case SPONSORED = 'sponsored';

    case PLANTED = 'planted';

    case ADOPTABLE = 'adoptable';

    case ADOPTED = 'adopted';

    public function label(): string
    {
        return match ($this) {
            self::SPONSORED => 'Sponsored',
            self::PLANTED => 'Planted',
            self::ADOPTABLE => 'Adoptable',
            self::ADOPTED => 'Adopted'
        };
    }
}
