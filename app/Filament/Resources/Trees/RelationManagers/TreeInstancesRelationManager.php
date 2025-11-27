<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trees\RelationManagers;

use App\Enums\AgeUnitEnum;
use App\Enums\TreeStatusEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class TreeInstancesRelationManager extends RelationManager
{
    protected static string $relationship = 'treeInstances';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('location_id')->native(false)->relationship('location', 'name'),
                Select::make('status')->native(false)->options(TreeStatusEnum::class)->required(),
                TextInput::make('age')->numeric(),
                Select::make('age_unit')->options(AgeUnitEnum::class)->native(false)->required(),
                TextInput::make('lat')->numeric(),
                TextInput::make('lng')->numeric(),
                DateTimePicker::make('planted_at'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('location.name')->searchable(),
                TextColumn::make('status')->badge()->searchable(),
                TextColumn::make('age')->numeric()->sortable(),
                TextColumn::make('age_unit')->searchable(),
                TextColumn::make('lat')->numeric()->sortable(),
                TextColumn::make('lng')->numeric()->sortable(),
                TextColumn::make('planted_at')->dateTime()->sortable(),
            ])
            ->filters([])
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
