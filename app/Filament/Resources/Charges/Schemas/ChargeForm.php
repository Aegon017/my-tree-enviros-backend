<?php

declare(strict_types=1);

namespace App\Filament\Resources\Charges\Schemas;

use App\Enums\ChargeModeEnum;
use App\Enums\ChargeTypeEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class ChargeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),

                TextInput::make('label')
                    ->required()
                    ->maxLength(120),

                Select::make('type')
                    ->required()
                    ->options(ChargeTypeEnum::options()),

                Select::make('mode')
                    ->required()
                    ->options(ChargeModeEnum::options())
                    ->default(ChargeModeEnum::FIXED->value),

                TextInput::make('value')
                    ->numeric()
                    ->nullable(),

                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
