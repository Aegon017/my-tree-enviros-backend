<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class OrderPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderPayments';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('amount')->numeric()->sortable(),
                TextColumn::make('payment_method')->searchable(),
                TextColumn::make('transaction_id')->searchable(),
                TextColumn::make('status')->searchable(),
                TextColumn::make('paid_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ]);
    }
}
