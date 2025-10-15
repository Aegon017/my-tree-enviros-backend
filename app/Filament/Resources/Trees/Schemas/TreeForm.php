<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trees\Schemas;

use App\Enums\AgeUnitEnum;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class TreeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()->schema([
                    Section::make('Details')->schema([
                        Flex::make([
                            TextInput::make('sku')
                                ->label('SKU')
                                ->default(fn (): string => (string) Str::uuid())
                                ->readOnly()
                                ->required(),
                            Toggle::make('is_active')
                                ->required()
                                ->Inline(false)
                                ->grow(false),
                        ]),
                        Flex::make([
                            TextInput::make('name')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('slug', Str::slug($state)))
                                ->required(),
                            TextInput::make('slug')
                                ->readOnly()
                                ->required(),
                        ]),
                        Flex::make([
                            TextInput::make('age')
                                ->required()
                                ->numeric(),
                            Select::make('age_unit')
                                ->options(AgeUnitEnum::options())
                                ->native(false)
                                ->required(),
                        ]),
                        RichEditor::make('description')
                            ->required()
                            ->columnSpanFull()
                            ->extraInputAttributes([
                                'style' => 'min-height: 16rem; overflow-y: auto;',
                            ]),
                    ])->columnSpan(8),
                    Section::make('Media')->schema([
                        SpatieMediaLibraryFileUpload::make('thumbnail')->collection('thumbnails'),
                        SpatieMediaLibraryFileUpload::make('image')->collection('images')->multiple(),
                    ])->columnSpan(4),
                ])->columns(12)->columnSpanFull(),
            ]);
    }
}
