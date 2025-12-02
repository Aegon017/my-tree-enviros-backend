<?php

declare(strict_types=1);

namespace App\Enums;

enum MessagePriority: string
{
    case HIGH = 'high';

    case NORMAL = 'normal';
}
