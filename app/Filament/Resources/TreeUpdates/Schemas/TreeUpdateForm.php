<?php

declare(strict_types=1);

namespace App\Filament\Resources\TreeUpdates\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class TreeUpdateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('tree_instance_id')
                    ->required()
                    ->numeric(),
                TextInput::make('title'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('status'),
                DateTimePicker::make('updated_for'),
                TextInput::make('user_id')
                    ->numeric(),
            ]);
    }
}
