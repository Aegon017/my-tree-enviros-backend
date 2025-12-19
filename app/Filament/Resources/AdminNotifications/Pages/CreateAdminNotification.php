<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminNotifications\Pages;

use App\Filament\Resources\AdminNotifications\AdminNotificationResource;
use App\Jobs\SendAdminNotificationJob;
use Filament\Resources\Pages\CreateRecord;

final class CreateAdminNotification extends CreateRecord
{
    protected static string $resource = AdminNotificationResource::class;

    protected function afterCreate(): void
    {
        SendAdminNotificationJob::dispatch($this->record->id)->delay(now()->addSeconds(5));
    }
}
