<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminNotifications\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class AdminNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->required(),
                RichEditor::make('body')
                    ->required()
                    ->fileAttachmentsDirectory('notification-images')
                    ->fileAttachmentsVisibility('public'),
                TextInput::make('link')->url(),
                CheckboxList::make('channels')
                    ->options([
                        'database' => 'Database',
                        'fcm' => 'Push',
                        'mail' => 'Mail',
                    ])
                    ->required(),
                Radio::make('target.type')
                    ->options([
                        'all' => 'All Users',
                    ])
                    ->inline()
                    ->required(),
            ]);
    }
}
