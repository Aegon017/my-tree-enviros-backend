<?php

namespace App\Filament\Resources\Inventories\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()->schema([
                    Section::make()->schema([
                        Grid::make()->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->options(Product::query()->pluck('name', 'id'))
                                ->searchable()
                                ->native(false)
                                ->preload()
                                ->required(),
                            TextInput::make('stock_quantity')
                                ->numeric()
                                ->required(),

                            Toggle::make('is_instock')->label('Is Instock')->default(true),
                        ])->columns(3),
                    ])->columnSpanFull(),

                ])->columns(12)->columnSpanFull(),
            ]);
    }
}
