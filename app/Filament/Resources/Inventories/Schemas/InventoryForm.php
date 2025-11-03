<?php

declare(strict_types=1);

namespace App\Filament\Resources\Inventories\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
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
                        ->image(),
                ]),
            ]);
    }
}
