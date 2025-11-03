<?php

declare(strict_types=1);

namespace App\Filament\Resources\PushNotifications;

use App\Filament\Resources\PushNotifications\Pages\CreatePushNotification;
use App\Filament\Resources\PushNotifications\Pages\EditPushNotification;
use App\Filament\Resources\PushNotifications\Pages\ListPushNotifications;
use App\Filament\Resources\PushNotifications\Schemas\PushNotificationForm;
use App\Filament\Resources\PushNotifications\Tables\PushNotificationsTable;
use App\Models\PushNotification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class PushNotificationResource extends Resource
{
    protected static ?string $model = PushNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'PushNotification';

    public static function form(Schema $schema): Schema
    {
        return PushNotificationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PushNotificationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPushNotifications::route('/'),
            'create' => CreatePushNotification::route('/create'),
            'edit' => EditPushNotification::route('/{record}/edit'),
        ];
    }
}
