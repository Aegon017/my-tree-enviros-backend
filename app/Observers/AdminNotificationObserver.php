<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\SendAdminNotificationJob;
use App\Models\AdminNotification;

final class AdminNotificationObserver
{
    public function created(AdminNotification $adminNotification): void
    {
        // SendAdminNotificationJob::dispatch($adminNotification->id);
    }

    public function updated(AdminNotification $adminNotification): void
    {
        // SendAdminNotificationJob::dispatch($adminNotification->id);
    }
}
