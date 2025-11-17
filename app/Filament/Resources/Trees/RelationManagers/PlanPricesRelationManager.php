<?php

namespace App\Filament\Resources\Trees\RelationManagers;

use App\Enums\TreeTypeEnum;
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
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                    ->prefixIcon('heroicon-s-currency-rupee')
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('plan.duration'),
                TextColumn::make('plan.duration_unit'),
                TextColumn::make('location.name')->numeric()->sortable(),
                TextColumn::make('price')->money('INR')->sortable(),
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

    public function getTabs(): array
    {
        return [
            'sponsor' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereHas('plan', fn($plan) => $plan->where('type', TreeTypeEnum::SPONSOR->value))),
            'adopt' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereHas('plan', fn($plan) => $plan->where('type', TreeTypeEnum::ADOPT->value))),
        ];
    }
}
