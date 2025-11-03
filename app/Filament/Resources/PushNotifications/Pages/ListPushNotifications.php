<?php

declare(strict_types=1);

namespace App\Filament\Resources\PushNotifications\Pages;

use App\Filament\Resources\PushNotifications\PushNotificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListPushNotifications extends ListRecords
{
    protected static string $resource = PushNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
