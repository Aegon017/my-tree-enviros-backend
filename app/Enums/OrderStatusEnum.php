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

    case SHIPPED = 'shipped';

    case OUT_FOR_DELIVERY = 'out_for_delivery';

    case DELIVERED = 'delivered';

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
            self::SHIPPED => 'Shipped',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
        };
    }
}
