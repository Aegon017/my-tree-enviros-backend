<?php

namespace App\Filament\Resources\TreeInstances\Schemas;

use App\Enums\TreeStatusEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TreeInstanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('tree_id')
                    ->required()
                    ->numeric(),
                TextInput::make('location_id')
                    ->numeric(),
                Select::make('status')
                    ->options(TreeStatusEnum::class)
                    ->required(),
                TextInput::make('age')
                    ->numeric(),
                TextInput::make('age_unit')
                    ->required(),
                TextInput::make('lat')
                    ->numeric(),
                TextInput::make('lng')
                    ->numeric(),
                DateTimePicker::make('planted_at'),
            ]);
    }
}
