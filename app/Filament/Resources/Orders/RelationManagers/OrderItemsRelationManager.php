<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderItems';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->searchable(),
                TextColumn::make('product_variant_id')->numeric()->sortable(),
                TextColumn::make('campaign_id')->numeric()->sortable(),
                TextColumn::make('tree_id')->numeric()->sortable(),
                TextColumn::make('plan_id')->numeric()->sortable(),
                TextColumn::make('plan_price_id')->numeric()->sortable(),
                TextColumn::make('tree_instance_id')->numeric()->sortable(),
                TextColumn::make('sponsor_quantity')->numeric()->sortable(),
                TextColumn::make('quantity')->numeric()->sortable(),
                TextColumn::make('amount')->numeric()->sortable(),
                TextColumn::make('total_amount')->numeric()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ]);
    }
}
