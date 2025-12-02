<?php

declare(strict_types=1);

namespace App\Enums;

enum CurrencyEnum: string
{
    case USD = 'USD';

    case EUR = 'EUR';

    case INR = 'INR';

    case GBP = 'GBP';

    case JPY = 'JPY';

    public function label(): string
    {
        return match ($this) {
            self::USD => 'US Dollar',
            self::EUR => 'Euro',
            self::INR => 'Indian Rupee',
            self::GBP => 'British Pound',
            self::JPY => 'Japanese Yen',
        };
    }
}
