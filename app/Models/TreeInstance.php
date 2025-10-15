<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TreeStatusEnum;
use Illuminate\Database\Eloquent\Model;

final class TreeInstance extends Model
{
    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'status' => TreeStatusEnum::class,
    ];
}
