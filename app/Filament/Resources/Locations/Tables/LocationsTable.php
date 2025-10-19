<?php

declare(strict_types=1);

namespace App\Filament\Resources\Locations\Tables;

use App\Models\Location;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Location')
                    ->formatStateUsing(fn ($state, $record): string => str_repeat('— ', $record->depth()).$state)
                    ->searchable(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'country' => 'primary',
                        'state' => 'info',
                        'city' => 'success',
                        'area' => 'gray',
                    }),

                TextColumn::make('parent.name')->label('Parent'),

                IconColumn::make('is_active')->label('Active')->boolean()->toggleable(true),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'country' => 'Country',
                    'state' => 'State',
                    'city' => 'City',
                    'area' => 'Area',
                ]),
                TernaryFilter::make('is_active')->label('Active Status'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('disableWithChildren')
                    ->label('Disable with Children')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(function (Location $record): void {
                        $record->update(['is_active' => false]);

                        $descendants = $record->allDescendants();
                        foreach ($descendants as $child) {
                            $child->update(['is_active' => false]);
                        }

                        Notification::make()
                            ->title(sprintf('Disabled location and %s child(ren)', $descendants->count()))
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Location $record) => $record->is_active),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
