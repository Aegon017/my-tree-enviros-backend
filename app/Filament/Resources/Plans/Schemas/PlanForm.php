<?php

namespace App\Filament\Resources\Plans\Schemas;

use App\Enums\DurationUnitEnum;
use App\Enums\PlanTypeEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options(PlanTypeEnum::class)
                    ->required(),
                TextInput::make('duration')
                    ->required()
                    ->numeric(),
                Select::make('duration_unit')
                    ->options(DurationUnitEnum::class)
                    ->required(),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
