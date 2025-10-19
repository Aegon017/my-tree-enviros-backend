<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasLabelOptions;

enum IdentificationTypeEnum: string
{
    use HasLabelOptions;

    case QR = 'qr';
    case NFC = 'nfc';
    case RFID = 'rfid';

    public function label(): string
    {
        return match ($this) {
            self::QR => 'QR',
            self::NFC => 'NFC',
            self::RFID => 'RFID',
        };
    }
}
