<?php

namespace App\Filament\Resources\TreePricePlans\Schemas;

use App\Enums\AgeUnitEnum;
use App\Enums\TreeTypeEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TreePricePlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->label('SKU')
                    ->disabled(),
                TextInput::make('name')
                    ->required(),
                Select::make('type')
                    ->options(TreeTypeEnum::class)
                    ->required(),
                TextInput::make('duration')
                    ->required()
                    ->numeric(),
                Select::make('duration_type')
                    ->options(AgeUnitEnum::class)
                    ->required(),
                Toggle::make('is_active')
                    ->inline(false)
                    ->required(),
            ]);
    }
}
