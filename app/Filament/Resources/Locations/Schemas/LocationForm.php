<?php

namespace App\Filament\Resources\Locations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Parent Location')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->native(false)
                    ->preload()
                    ->nullable(),

                TextInput::make('name')->required(),

                Select::make('type')
                    ->required()
                    ->options([
                        'country' => 'Country',
                        'state' => 'State',
                        'city' => 'City',
                        'area' => 'Area',
                    ])
                    ->native(false),

                Toggle::make('is_active')->label('Is Active')->default(true),
            ]);
    }
}
