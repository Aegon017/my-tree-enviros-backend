<?php

namespace App\Observers;

use App\Jobs\SendAdminNotificationJob;
use App\Models\AdminNotification;

class AdminNotificationObserver
{
    public function created(AdminNotification $adminNotification): void
    {
        SendAdminNotificationJob::dispatch($adminNotification->id);
    }

    public function updated(AdminNotification $adminNotification): void
    {
        SendAdminNotificationJob::dispatch($adminNotification->id);
    }
}
