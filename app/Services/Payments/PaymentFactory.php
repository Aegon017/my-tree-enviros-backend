<?php

declare(strict_types=1);

namespace App\Services\Payments;

use InvalidArgumentException;

final class PaymentFactory
{
    public static function driver(string $gateway)
    {
        return match ($gateway) {
            'razorpay' => new RazorpayService(),
            default => throw new InvalidArgumentException('Unsupported payment gateway'),
        };
    }
}
