<?php

namespace App\Filament\Resources\TreeUpdates\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TreeUpdateForm
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
