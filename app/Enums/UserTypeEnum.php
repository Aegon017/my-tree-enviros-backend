<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum UserTypeEnum: string
{
    use HasLabelOptions;

    case INDIVIDUAL = 'individual';
    case ORGANIZATION = 'organization';

    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Individual',
            self::ORGANIZATION => 'Organization',
        };
    }
}
