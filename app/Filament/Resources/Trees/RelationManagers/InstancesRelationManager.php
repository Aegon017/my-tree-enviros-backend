<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trees\RelationManagers;

use App\Enums\TreeStatusEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class InstancesRelationManager extends RelationManager
{
    protected static string $relationship = 'instances';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->label('SKU')
                    ->disabled(),
                Select::make('location_id')
                    ->relationship('location', 'name')
                    ->native(false)
                    ->required(),
                Select::make('status')
                    ->options(TreeStatusEnum::class)
                    ->native(false)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('location.name')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
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
