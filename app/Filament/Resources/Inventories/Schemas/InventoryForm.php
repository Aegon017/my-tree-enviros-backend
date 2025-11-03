<?php

declare(strict_types=1);

namespace App\Filament\Resources\Inventories\Schemas;

use App\Models\Product;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class InventoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Product')
                    ->options(Product::query()->pluck('name', 'id'))
                    ->searchable()
                    ->native(false)
                    ->preload()
                    ->required(),
                Section::make('Media')->schema([
                    SpatieMediaLibraryFileUpload::make('thumbnail')
                        ->collection('thumbnail')
                        ->label('Product Thumbnail (for listings)')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '16:9',
                            '4:3',
                            '1:1',
                        ]),
                    SpatieMediaLibraryFileUpload::make('images')
                        ->collection('images')
                        ->label('Product Images (global images)')
                        ->multiple()
                        ->image()
                        ->reorderable()
                        ->imageEditor(),
                ]),
            ]);
    }
}
