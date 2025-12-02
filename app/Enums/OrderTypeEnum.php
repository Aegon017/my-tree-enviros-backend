<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum OrderTypeEnum: string
{
    use HasLabelOptions;

    case SPONSOR = 'sponsor';

    case ADOPT = 'adopt';

    case PRODUCT = 'product';

    case CAMPAIGN = 'campaign';

    public function label(): string
    {
        return match ($this) {
            self::SPONSOR => 'Sponsor',
            self::ADOPT => 'Adopt',
            self::PRODUCT => 'Product',
            self::CAMPAIGN => 'Campaign'
        };
    }
}
