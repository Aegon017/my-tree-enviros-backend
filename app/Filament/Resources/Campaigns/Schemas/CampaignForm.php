<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Enums\CampaignTypeEnum;
use Filament\Forms\Components\DatePicker;
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
use Symfony\Component\Yaml\Inline;

final class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()->schema([
                Section::make('Details')->schema([
                    Flex::make([
                        Select::make('type')
                            ->label('Type')
                            ->options(CampaignTypeEnum::options())
                            ->required()
                            ->native(false)
                            ->searchable(),
                        Toggle::make('is_active')->label('Active')->default(true)->grow(false)->inline(false),
                    ]),
                    Flex::make([
                        Select::make('location_id')
                            ->label('Location')
                            ->relationship('location', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                fn(Set $set, ?string $state): mixed => $set(
                                    'slug',
                                    Str::slug($state ?? ''),
                                ),
                            ),

                        ]),
                        Flex::make([
                            TextInput::make('slug')
                                ->label('Slug')
                                ->unique(table: 'campaigns', column: 'slug')
                                ->readOnly()
                                ->required(),
                            TextInput::make('amount')
                            ->label('Suggested Amount (INR)')
                            ->numeric()
                            ->step(0.01)
                            ->required(),
                    ]),
                    Flex::make([
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->native(false)
                            ->closeOnDateSelection(),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->native(false)
                            ->closeOnDateSelection()
                    ]),

                    RichEditor::make('description')
                        ->label('Description'),
                ])->columnSpan(8),

                Section::make('Media')->schema([
                    SpatieMediaLibraryFileUpload::make('thumbnail')
                        ->label('Thumbnail')
                        ->collection('thumbnail')
                        ->image(),

                    SpatieMediaLibraryFileUpload::make('images')
                        ->label('Gallery Images')
                        ->collection('images')
                        ->image()
                        ->multiple()
                        ->reorderable(),
                ])->columnSpan(4),
            ])->columns(12)->columnSpanFull(),
        ]);
    }
}
