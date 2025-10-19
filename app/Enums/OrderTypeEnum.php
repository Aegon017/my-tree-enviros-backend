<?php

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum OrderTypeEnum: string
{
    use HasLabelOptions;

    case PRODUCT = 'product';
    case SPONSOR = 'sponsor';
    case ADOPT = 'adopt';
    case CAMPAIGN = 'campaign';

    public function label(): string
    {
        return match ($this) {
            self::PRODUCT => 'Product',
            self::SPONSOR => 'Sponsor',
            self::ADOPT => 'Adopt',
            self::CAMPAIGN => 'Campaign',
        };
    }
}
