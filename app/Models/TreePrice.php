<?php

namespace App\Models;

use App\Enums\AgeUnitEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreePrice extends Model
{
    protected $casts = [
        'price' => 'decimal:2',
        'duration' => 'integer',
        'duration_type' => AgeUnitEnum::class,
        'is_active' => 'boolean'
    ];

    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class);
    }
}
