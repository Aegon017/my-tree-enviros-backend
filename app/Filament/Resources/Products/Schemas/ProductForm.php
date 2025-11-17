<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Models\ProductCategory;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Flex;
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
                    Section::make('Details')->schema([
                        Flex::make([
                            Select::make('product_category_id')
                                ->label('Category')
                                ->options(ProductCategory::query()->pluck('name', 'id'))
                                ->searchable()
                                ->native(false)
                                ->preload()
                                ->columns(8)
                                ->required(),
                            Toggle::make('is_active')->required()
                                ->Inline(false)
                                ->default(true)
                                ->grow(false),
                        ]),
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Set $set, ?string $state): mixed => $set('slug', Str::slug($state))),
                                TextInput::make('slug')
                                    ->unique(table: 'products', column: 'slug')
                                    ->readOnly()
                                    ->required(),
                                TextInput::make('botanical_name')
                                    ->required(),
                                TextInput::make('nick_name')
                                    ->required(),
                            ]),
                        Textarea::make('short_description')->required(),
                        RichEditor::make('description')->required(),
                    ])->columnSpanFull(),

                ])->columns(12)->columnSpanFull(),
            ]);
    }
}
