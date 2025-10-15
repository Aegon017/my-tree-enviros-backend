<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('images')
                    ->toggleable(true),
                TextColumn::make('productCategory.name')
                    ->label('Category')
                    ->searchable()
                    ->toggleable(true),
                TextColumn::make('name')
                    ->searchable()
                    ->toggleable(true),
                TextColumn::make('botanical_name')
                    ->toggleable(true),
                TextColumn::make('nick_name')->toggleable(true),
                TextColumn::make('base_price')->toggleable(true),
                TextColumn::make('discount_price')->toggleable(true),
                IconColumn::make('is_active')->label('Active')->boolean()->toggleable(true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
