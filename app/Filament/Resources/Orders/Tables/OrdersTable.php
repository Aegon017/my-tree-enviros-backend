<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;

final class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->searchable(),
                TextColumn::make('user.name')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('items.type')->label('Item Types')->listWithLineBreaks()->separator(', '),
                TextColumn::make('grand_total'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([])
            ->recordActions([
                MediaAction::make('invoice')
                    ->label('Invoice')
                    ->media(fn ($record): string => route('orders.invoice', $record))
                    ->mediaType(MediaAction::TYPE_PDF),
            ]);
    }
}
