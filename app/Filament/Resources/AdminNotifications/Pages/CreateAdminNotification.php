<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminNotifications\Pages;

use App\Filament\Resources\AdminNotifications\AdminNotificationResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateAdminNotification extends CreateRecord
{
    protected static string $resource = AdminNotificationResource::class;
}
