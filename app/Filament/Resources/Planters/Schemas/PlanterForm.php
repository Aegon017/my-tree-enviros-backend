<?php

declare(strict_types=1);

namespace App\Filament\Resources\Planters\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class PlanterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                SpatieMediaLibraryFileUpload::make('image')->collection('images')->required(),
            ]);
    }
}
