<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatusEnum: string
{
    case PENDING = 'pending';

    case PAID = 'paid';

    case FAILED = 'failed';

    case SUCCESS = 'success';

    case CANCELLED = 'cancelled';

    case REFUNDED = 'refunded';

    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::FAILED => 'Failed',
            self::SUCCESS => 'Success',
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
            self::COMPLETED => 'Completed',
        };
    }
}
