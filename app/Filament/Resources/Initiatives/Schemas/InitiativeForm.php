<?php

namespace App\Filament\Resources\Initiatives\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InitiativeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(255),

                Select::make('primary_location_id')
                    ->label('Primary Location')
                    ->relationship('primaryLocation', 'name')
                    ->searchable()
                    ->preload(),

                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->required()
                    ->default('active'),
            ]);
    }
}
