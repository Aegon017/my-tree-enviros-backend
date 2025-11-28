<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Charge extends Model
{
    protected $casts = [
        'value' => 'decimal:4',
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    public function orderCharges(): HasMany
    {
        return $this->hasMany(OrderCharge::class);
    }
}
