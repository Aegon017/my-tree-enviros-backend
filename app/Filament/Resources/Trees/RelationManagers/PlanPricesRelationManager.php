<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trees\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PlanPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'planPrices';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->label('SKU')
                    ->disabled(),
                Select::make('tree_price_plan_id')
                    ->relationship('plan', 'name')
                    ->native(false)
                    ->required(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('INR'),
                Toggle::make('is_active')
                    ->inline(false)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan.name')->label('Plan'),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('price')
                    ->money('INR')
                    ->sortable(),
                IconColumn::make('is_active')->boolean(),
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
