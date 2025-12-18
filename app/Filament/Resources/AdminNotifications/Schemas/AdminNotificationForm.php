<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminNotifications\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class AdminNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->required(),
                Textarea::make('body')->required(),
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
