<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PaymentAttempt extends Model
{
    protected $casts = [
        'shipping_address_snapshot' => 'array',
        'metadata' => 'array',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PaymentAttemptItem::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(PaymentAttemptCharge::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(ShippingAddress::class);
    }

    public function createdOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'created_order_id');
    }
}
