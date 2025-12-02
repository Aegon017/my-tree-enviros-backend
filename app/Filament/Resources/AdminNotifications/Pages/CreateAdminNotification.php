<?php

namespace App\Filament\Resources\AdminNotifications\Pages;

use App\Filament\Resources\AdminNotifications\AdminNotificationResource;
use App\Jobs\SendAdminNotificationJob;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminNotification extends CreateRecord
{
    protected static string $resource = AdminNotificationResource::class;
}
