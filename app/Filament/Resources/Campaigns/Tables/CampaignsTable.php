<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class CampaignsTable
{
    public static function schema(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('thumbnail_url')
                    ->label('Thumbnail')
                    ->collection('thumbnails')
                    ->imageHeight('8rem'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('location.name')
                    ->label('Location')
                    ->searchable(),

                TextColumn::make('target_amount')
                    ->label('Target Amount')
                    ->money('INR'),

                TextColumn::make('start_date')
                    ->label('Start')
                    ->date(),

                TextColumn::make('end_date')
                    ->label('End')
                    ->date(),

                ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->nullable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }
}
