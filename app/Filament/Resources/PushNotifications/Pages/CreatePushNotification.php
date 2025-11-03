<?php

declare(strict_types=1);

namespace App\Filament\Resources\PushNotifications\Pages;

use App\Filament\Resources\PushNotifications\PushNotificationResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePushNotification extends CreateRecord
{
    protected static string $resource = PushNotificationResource::class;
}
