<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Models\ProductCategory;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()->schema([
                    Section::make()->schema([
                        Select::make('product_category_id')
                            ->label('Category')
                            ->options(ProductCategory::query()->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->preload()
                            ->required(),
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('slug', Str::slug($state))),
                                TextInput::make('slug')
                                    ->unique(table: 'products', column: 'slug')
                                    ->required(),
                                TextInput::make('botanical_name')
                                    ->required(),
                                TextInput::make('nick_name')
                                    ->required(),
                                TextInput::make('base_price')
                                    ->required(),
                                TextInput::make('discount_price')
                                    ->nullable(),
                                Toggle::make('is_active')->label('Is Active')->default(true),
                            ]),
                        Textarea::make('short_description')->required(),
                        RichEditor::make('description')->required(),
                    ])->columnSpan(8),
                    Section::make('Media')->schema([
                        SpatieMediaLibraryFileUpload::make('thumbnail')
                            ->collection('thumbnails')
                            ->required(),
                        SpatieMediaLibraryFileUpload::make('image')
                            ->collection('images')
                            ->nullable()
                            ->multiple(),
                    ])->columnSpan(4),

                ])->columns(12)->columnSpanFull(),
            ]);
    }
}
