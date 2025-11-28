<?php

declare(strict_types=1);

namespace App\Filament\Resources\Charges\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class ChargeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->required(),
                TextInput::make('label')->required(),
                TextInput::make('type')->required(),
                TextInput::make('mode')->required()->default('fixed'),
                TextInput::make('value')->numeric(),
                TextInput::make('meta'),
                Toggle::make('is_active')->required(),
            ]);
    }
}
