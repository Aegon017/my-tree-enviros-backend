<?php

namespace App\Filament\Resources\Sliders\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SliderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()->schema([
                    Section::make('Details')->schema([
                        Flex::make([
                            TextInput::make('title'),
                            Toggle::make('is_active')->required()
                                ->Inline(false)
                                ->grow(false),
                        ]),
                        Textarea::make('description'),
                    ])->columnSpan(8),
                    Section::make('Media')->schema([
                        SpatieMediaLibraryFileUpload::make('image')->collection('images')->required(),
                    ])->columnSpan(4),

                ])->columns(12)->columnSpanFull(),
            ]);
    }
}
