<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethodEnum: string
{
    case RAZORPAY = 'razorpay';
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::RAZORPAY => 'Razorpay',
            self::STRIPE => 'Stripe',
            self::PAYPAL => 'Paypal',
            self::MANUAL => 'Manual',
        };
    }
}
