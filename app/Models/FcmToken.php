<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeviceTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FcmToken extends Model
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
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function ofDeviceType($query, DeviceTypeEnum $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope a query to only include active tokens (not expired).
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function active($query)
    {
        return $query->where('updated_at', '>=', now()->subDays(30));
    }
}
