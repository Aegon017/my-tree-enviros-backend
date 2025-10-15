<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TreeLocation extends Model
{
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
