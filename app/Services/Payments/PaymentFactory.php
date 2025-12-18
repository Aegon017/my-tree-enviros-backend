<?php

declare(strict_types=1);

namespace App\Services\Payments;

use InvalidArgumentException;

final class PaymentFactory
{
    public static function driver(string $gateway): RazorpayService|PhonepeService
    {
        return match ($gateway) {
            'razorpay' => new RazorpayService(),
            'phonepe' => new PhonepeService(),
            default => throw new InvalidArgumentException('Unsupported payment gateway'),
        };
    }
}
