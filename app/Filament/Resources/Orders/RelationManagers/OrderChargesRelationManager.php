<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class OrderChargesRelationManager extends RelationManager
{
    protected static string $relationship = 'orderCharges';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('charge.id')->searchable(),
                TextColumn::make('type')->searchable(),
                TextColumn::make('label')->searchable(),
                TextColumn::make('amount')->numeric()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ]);
    }
}
