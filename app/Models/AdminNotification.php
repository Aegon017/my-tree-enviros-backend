<?php

namespace App\Models;

use App\Observers\AdminNotificationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([AdminNotificationObserver::class])]
class AdminNotification extends Model
{
    protected $casts = ['channels' => 'array', 'target' => 'array'];
}
