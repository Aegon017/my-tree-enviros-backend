<?php

declare(strict_types=1);

namespace App\Filament\Resources\PushNotifications\Pages;

use App\Filament\Resources\PushNotifications\PushNotificationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditPushNotification extends EditRecord
{
    protected static string $resource = PushNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
