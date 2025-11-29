<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Order extends Model
{
    protected $fillable = [
        'user_id',
        'reference_number',
        'status',
        'subtotal',
        'total_discount',
        'total_tax',
        'total_shipping',
        'total_fee',
        'grand_total',
        'shipping_address_id',
        'coupon_id',
        'payment_method',
        'currency',
        'paid_at',
    ];

    protected $casts = [
        'status' => OrderStatusEnum::class,
        'subtotal' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_shipping' => 'decimal:2',
        'total_fee' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(OrderPayment::class)->latestOfMany();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(ShippingAddress::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderPayments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function orderCharges(): HasMany
    {
        return $this->hasMany(OrderCharge::class);
    }
}
