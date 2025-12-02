<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum DeviceTypeEnum: string
{
    use HasLabelOptions;

    case ANDROID = 'android';

    case IOS = 'ios';

    case WEB = 'web';

    public function label(): string
    {
        return match ($this) {
            self::ANDROID => 'Android',
            self::IOS => 'IOS',
            self::WEB => 'Web',
        };
    }
}
