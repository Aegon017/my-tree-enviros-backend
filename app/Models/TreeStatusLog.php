<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TreeStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TreeStatusLog extends Model
{
    protected $casts = [
        'status' => TreeStatusEnum::class,
        'changed_at' => 'datetime',
    ];

    public function treeInstance(): BelongsTo
    {
        return $this->belongsTo(TreeInstance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
