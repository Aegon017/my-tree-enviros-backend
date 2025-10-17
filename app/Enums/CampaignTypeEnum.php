<?php

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum CampaignTypeEnum: string
{
    use HasLabelOptions;

    case FEED = 'feed';
    case PROTECT = 'protect';
    case PLANT = 'plant';

    public function label(): string
    {
        return match ($this) {
            self::FEED => 'Feed',
            self::PROTECT => 'Protect',
            self::PLANT => 'Plant',
        };
    }
}
