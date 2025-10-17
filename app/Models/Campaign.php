<?php

namespace App\Models;

use App\Enums\CampaignTypeEnum;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $casts = [
        'type' => CampaignTypeEnum::class,
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'data',
        'is_active' => 'boolean'
    ];
}
