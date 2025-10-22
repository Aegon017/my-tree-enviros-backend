<?php

declare(strict_types=1);

namespace App\Filament\Resources\Inventories\RelationManagers;

use App\Models\Variant;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ProductVariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'productVariants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('variant_id')
                    ->label('Variant')
                    ->options(
                        Variant::with(['color', 'size', 'planter'])->get()->mapWithKeys(function ($variant) {
                            return [
                                $variant->id => "{$variant->color->name} - {$variant->size->name} - {$variant->planter->name}",
                            ];
                        })
                    )
                    ->searchable()
                    ->native(false)
                    ->preload()
                    ->required(),
                TextInput::make('sku')->required(),
                TextInput::make('base_price')->required(),
                TextInput::make('discount_price')->required(),
                TextInput::make('stock_quantity')->numeric()->required(),
                Toggle::make('is_instock')->label('In Stock')->default(true),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ProductVariant')
            ->columns([
                TextColumn::make('variant.color.name')->label('Color'),
                TextColumn::make('variant.size.name')->label('Size'),
                TextColumn::make('variant.planter.name')->label('Planter'),
                TextColumn::make('sku'),
                TextColumn::make('base_price'),
                TextColumn::make('discount_price'),
                TextColumn::make('stock_quantity'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
