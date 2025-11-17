<?php

namespace App\Filament\Resources\PlanPrices\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class PlanPriceForm
{
    public static function configure(Schema $schema): Schema
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
}
