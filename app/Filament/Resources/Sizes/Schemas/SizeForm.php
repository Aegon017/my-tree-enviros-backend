<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sizes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class SizeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
            ]);
    }
}
