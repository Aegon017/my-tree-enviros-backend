<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\Tables;

use App\Enums\CampaignTypeEnum;
use App\Models\Campaign;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class CampaignsTable
{
    /**
     * Build the reusable Campaigns table schema.
     */
    public static function schema(Table $table): Table
    {
        $typeColors = [
            "feed" => "success",
            "protect" => "warning",
            "plant" => "info",
        ];

        return $table
            ->columns([
                Tables\Columns\ImageColumn::make("thumbnail_url")
                    ->label("Thumbnail")
                    ->circular()
                    ->getStateUsing(
                        fn(Campaign $record) => $record->getFirstMediaUrl(
                            "thumbnails",
                        ),
                    )
                    ->defaultImageUrl(
                        fn(Campaign $record) => $record->getFirstMediaUrl(
                            "images",
                        ) ?:
                        null,
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make("name")
                    ->label("Name")
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make("type")
                    ->label("Type")
                    ->badge()
                    ->formatStateUsing(
                        fn(Campaign $record) => $record->type?->label() ??
                            Str::title((string) $record->type),
                    )
                    ->color(
                        fn(Campaign $record) => $typeColors[
                            $record->type?->value
                        ] ?? "gray",
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make("location.name")
                    ->label("Location")
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make("amount")
                    ->label("Amount")
                    ->money("INR", locale: "en_IN")
                    ->sortable(),

                Tables\Columns\TextColumn::make("start_date")
                    ->label("Start")
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make("end_date")
                    ->label("End")
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\ToggleColumn::make("is_active")
                    ->label("Active")
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make("type")
                    ->label("Type")
                    ->options(
                        collect(CampaignTypeEnum::cases())
                            ->mapWithKeys(
                                fn(CampaignTypeEnum $e) => [
                                    $e->value => $e->label(),
                                ],
                            )
                            ->all(),
                    ),

                Tables\Filters\TernaryFilter::make("is_active")
                    ->label("Active")
                    ->trueLabel("Active")
                    ->falseLabel("Inactive")
                    ->nullable(),

                Tables\Filters\Filter::make("expired")
                    ->label("Expired")
                    ->query(
                        fn(Builder $query) => $query
                            ->whereNotNull("end_date")
                            ->whereDate("end_date", "<", now()),
                    ),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort("created_at", "desc")
            ->paginated([25, 50, 100]);
    }
}
