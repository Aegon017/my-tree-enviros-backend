<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum TreeStatusEnum: string
{
    use HasLabelOptions;

    case AVAILABLE = 'available';
    case SPONSORED = 'sponsored';
    case EXPIRED = 'expired';
    case ADOPTED = 'adopted';
    case MAINTENANCE = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::SPONSORED => 'Sponsored',
            self::EXPIRED => 'Expired',
            self::ADOPTED => 'Adopted',
            self::MAINTENANCE => 'Maintenance',
        };
    }
}
