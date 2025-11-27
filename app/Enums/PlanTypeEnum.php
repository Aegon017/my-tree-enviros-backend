<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum PlanTypeEnum: string
{
    use HasLabelOptions;

    case SPONSOR = 'sponsor';
    case ADOPT = 'adopt';

    public function label(): string
    {
        return match ($this) {
            self::SPONSOR => 'Sponsor',
            self::ADOPT => 'Adopt'
        };
    }
}
