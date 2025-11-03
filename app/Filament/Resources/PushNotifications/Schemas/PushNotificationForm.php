<?php

declare(strict_types=1);

namespace App\Filament\Resources\PushNotifications\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class PushNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()->schema([
                    Section::make('Details')->schema([
                        TextInput::make('title')->required(),
                        Textarea::make('text')->required(),
                    ])->columnSpan(8),
                    Section::make('Media')->schema([
                        SpatieMediaLibraryFileUpload::make('image')->collection('images')->required(),
                    ])->columnSpan(4),

                ])->columns(12)->columnSpanFull(),
            ]);
    }
}
