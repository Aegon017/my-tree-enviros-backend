<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;

final class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', direction: 'desc')
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
                ActionGroup::make([
                    MediaAction::make('invoice')
                        ->icon(Heroicon::OutlinedArrowDownOnSquareStack)
                        ->label('Invoice')
                        ->media(fn($record): string => route('admin.orders.invoice', $record))
                        ->mediaType(MediaAction::TYPE_PDF),
                ]),
            ], position: RecordActionsPosition::BeforeCells);
    }
}
