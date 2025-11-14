<?php

namespace App\Filament\Resources\Trees\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TreeLocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'treeLocations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('location_id')->relationship('location', 'name')->native(false)->required(),
                Toggle::make('is_active')->default(true)->required()
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('location.name')->searchable(),
                IconColumn::make('is_active')->boolean()
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                ]),
            ]);
    }
}
