<?php

namespace App\Filament\Resources\Initiatives\RelationManagers;

use App\Models\Location;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class SitesRelationManager extends RelationManager
{
    protected static string $relationship = 'sites';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('Site Label')
                    ->placeholder('e.g., Durgam Cheruvu Park – Lakeside Path')
                    ->required()
                    ->maxLength(255),

                Select::make('location_id')
                    ->label('Area')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('latitude')
                    ->numeric()
                    ->maxValue(90)
                    ->minValue(-90),
                TextInput::make('longitude')
                    ->numeric()
                    ->maxValue(180)
                    ->minValue(-180),
                TextInput::make('capacity')->numeric()->label('Max Trees Allowed'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('label')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('location.name')->label('Area')->sortable(),
                Tables\Columns\TextColumn::make('capacity')->sortable()->label('Capacity'),
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
