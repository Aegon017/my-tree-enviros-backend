<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\AdminNotificationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([AdminNotificationObserver::class])]
final class AdminNotification extends Model
{
    protected $casts = ['channels' => 'array', 'target' => 'array'];

    protected $fillable = ['title', 'body', 'channels', 'target', 'link'];
}
