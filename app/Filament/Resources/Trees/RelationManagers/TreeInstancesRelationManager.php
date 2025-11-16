<?php

namespace App\Filament\Resources\Trees\RelationManagers;

use App\Enums\TreeStatusEnum;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TreeInstancesRelationManager extends RelationManager
{
    protected static string $relationship = 'treeInstances';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('location_id')
                    ->relationship('location', 'name'),
                Select::make('status')
                    ->options(TreeStatusEnum::class)
                    ->required(),
                TextInput::make('age')
                    ->numeric(),
                TextInput::make('age_unit')
                    ->required(),
                TextInput::make('lat')
                    ->numeric(),
                TextInput::make('lng')
                    ->numeric(),
                DateTimePicker::make('planted_at'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('location.name')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('age')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('age_unit')
                    ->searchable(),
                TextColumn::make('lat')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lng')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('planted_at')
                    ->dateTime()
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
