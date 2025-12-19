<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Cart extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function totalAmount(): float
    {
        $this->loadMissing('items');

        $total = 0.0;

        foreach ($this->items as $item) {
            $itemTotal = null;

            if (isset($item->total_amount) && $item->total_amount !== null) {
                $itemTotal = (float) $item->total_amount;
            } elseif (isset($item->amount) && isset($item->quantity)) {
                $itemTotal = (float) $item->amount * (int) $item->quantity;
            } else {
                $itemTotal = 0.0;
            }

            $total += $itemTotal;
        }

        return $total;
    }
}
