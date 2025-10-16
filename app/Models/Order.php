<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatusEnum;
use App\Enums\TreeTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Order extends Model
{
    protected $casts = [
        'type' => TreeTypeEnum::class,
        'status' => OrderStatusEnum::class,
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function paid($query)
    {
        return $query->where('status', 'paid');
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function sponsorship($query)
    {
        return $query->where('type', 'sponsorship');
    }
}
