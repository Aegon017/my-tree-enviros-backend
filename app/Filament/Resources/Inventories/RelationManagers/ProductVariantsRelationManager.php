<?php

declare(strict_types=1);

namespace App\Filament\Resources\Inventories\RelationManagers;

use App\Models\Variant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                    ->label('Variant (Optional)')
                    ->options(function (): array {
                        $options = [];
                        $options['base'] = 'Base Product (No Variant)';

                        $variants = Variant::with(['color', 'size', 'planter'])->get();
                        foreach ($variants as $variant) {
                            $options[$variant->id] = sprintf('%s - %s - %s',
                                $variant->color->name,
                                $variant->size->name,
                                $variant->planter->name
                            );
                        }

                        return $options;
                    })
                    ->dehydrateStateUsing(fn ($state) => $state === 'base' ? null : $state)
                    ->afterStateHydrated(function ($component, $state): void {
                        $component->state($state ?? 'base');
                    })
                    ->searchable()
                    ->native(false)
                    ->preload(),
                TextInput::make('sku')->disabled(),
                TextInput::make('original_price')->numeric()->required(),
                TextInput::make('selling_price')->numeric(),
                TextInput::make('stock_quantity')->numeric()->required(),
                Toggle::make('is_instock')->label('In Stock')->default(true),
                SpatieMediaLibraryFileUpload::make('images')
                    ->collection('images')
                    ->label('Variant Images')
                    ->multiple()
                    ->image()
                    ->reorderable()
                    ->helperText('Upload images specific to this variant. These will be displayed when this variant is selected.')
                    ->columnSpanFull(),
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
                TextColumn::make('variant.color.name')
                    ->label('Color')
                    ->formatStateUsing(fn (?string $state): string => $state ?? 'Base Product'),
                TextColumn::make('variant.size.name')
                    ->label('Size')
                    ->formatStateUsing(fn (?string $state): string => $state ?? '-'),
                TextColumn::make('variant.planter.name')
                    ->label('Planter')
                    ->formatStateUsing(fn (?string $state): string => $state ?? '-'),
                TextColumn::make('sku')->label('SKU'),
                TextColumn::make('original_price'),
                TextColumn::make('selling_price'),
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
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
