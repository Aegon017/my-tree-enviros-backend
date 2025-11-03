<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('productCategory.name')
                    ->label('Category')
                    ->searchable()
                    ->toggleable(true),
                TextColumn::make('name')->searchable()->toggleable(true),
                TextColumn::make('botanical_name')->toggleable(true),
                TextColumn::make('nick_name')->toggleable(true),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->toggleable(true),
            ])
            ->filters([
                //
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
