<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trees\RelationManagers;

use App\Enums\AgeUnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class TreePricesRelationManager extends RelationManager
{
    protected static string $relationship = 'treePrices';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('INR'),
                TextInput::make('duration')
                    ->numeric()
                    ->required(),
                Select::make('duration_type')
                    ->options(AgeUnitEnum::options())
                    ->native(false)
                    ->required(),
                Toggle::make('is_active')
                    ->inline(false)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('price')
            ->columns([
                TextColumn::make('price')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('duration')
                    ->searchable(),
                TextColumn::make('duration_type')
                    ->badge()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
