<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeviceTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcmToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'device_type',
    ];

    protected $casts = [
        'device_type' => DeviceTypeEnum::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include tokens of a given device type.
     */
    public function scopeOfDeviceType($query, DeviceTypeEnum $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope a query to only include active tokens (not expired).
     */
    public function scopeActive($query)
    {
        return $query->where('updated_at', '>=', now()->subDays(30));
    }
}
