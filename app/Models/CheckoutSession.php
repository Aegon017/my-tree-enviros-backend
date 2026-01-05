<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CheckoutSession extends Model
{
    protected $guarded = [];

    protected $casts = [
        'items' => 'array',
        'pricing' => 'array',
        'shipping_address_snapshot' => 'array',
        'gateway_response' => 'array',
        'coupon_discount' => 'decimal:2',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(ShippingAddress::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Check if session is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    /**
     * Mark session as completed
     */
    public function markCompleted(string $gatewayPaymentId): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'gateway_payment_id' => $gatewayPaymentId,
        ]);
    }

    /**
     * Mark session as expired
     */
    public function markExpired(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Mark session as cancelled
     */
    public function markCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }
}
