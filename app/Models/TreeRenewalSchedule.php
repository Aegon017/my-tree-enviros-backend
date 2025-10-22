<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TreeRenewalSchedule extends Model
{
    protected $fillable = [
        'order_item_id',
        'reminder_date',
        'reminder_sent',
    ];

    protected $casts = [
        'reminder_date' => 'datetime',
        'reminder_sent' => 'boolean',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
