<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum ChargeTypeEnum: string
{
    use HasLabelOptions;

    case TAX = 'tax';
    case SHIPPING = 'shipping';
    case FEE = 'fee';
    case SERVICE = 'service';
    case CONVENIENCE = 'convenience';

    public function label(): string
    {
        return match ($this) {
            self::TAX => 'Tax',
            self::SHIPPING => 'Shipping',
            self::FEE => 'Fee',
            self::SERVICE => 'Service',
            self::CONVENIENCE => 'Convenience',
        };
    }
}
