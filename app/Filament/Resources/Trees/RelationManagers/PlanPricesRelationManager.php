<?php

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
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PlanPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'planPrices';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('plan_id')
                    ->relationship('plan', 'id')
                    ->getOptionLabelFromRecordUsing(fn(Model $record) => "{$record->duration} {$record->duration_unit->label()} ({$record->type->label()})")
                    ->required()
                    ->preload()
                    ->native(false)
                    ->searchable(),
                Select::make('location_id')->relationship('location', 'name')->native(false)->preload()->searchable(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('INR'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('plan.id')
                    ->searchable(),
                TextColumn::make('location_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
