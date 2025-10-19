<?php

namespace App\Models;

use App\Enums\CampaignTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    protected $casts = [
        'type' => CampaignTypeEnum::class,
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'data',
        'is_active' => 'boolean'
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnails')->singleFile();
        $this->addMediaCollection('images');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    #[Scope]
    protected function active($query)
    {
        return $query->where('is_active', true);
    }
}
