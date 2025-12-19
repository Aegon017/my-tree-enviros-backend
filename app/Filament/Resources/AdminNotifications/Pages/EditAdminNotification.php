<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminNotifications\Pages;

use App\Filament\Resources\AdminNotifications\AdminNotificationResource;
use App\Jobs\SendAdminNotificationJob;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditAdminNotification extends EditRecord
{
    protected static string $resource = AdminNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        SendAdminNotificationJob::dispatch($this->record->id)->delay(now()->addSeconds(5));
    }
}
