<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IdentificationTypeEnum;
use Illuminate\Database\Eloquent\Model;

final class TreeIdentifier extends Model
{
    protected $casts = [
        'type' => IdentificationTypeEnum::class,
    ];
}
