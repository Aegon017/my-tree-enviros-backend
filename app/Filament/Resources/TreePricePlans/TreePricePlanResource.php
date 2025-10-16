<?php

namespace App\Filament\Resources\TreePricePlans;

use App\Filament\Resources\TreePricePlans\Pages\CreateTreePricePlan;
use App\Filament\Resources\TreePricePlans\Pages\EditTreePricePlan;
use App\Filament\Resources\TreePricePlans\Pages\ListTreePricePlans;
use App\Filament\Resources\TreePricePlans\Schemas\TreePricePlanForm;
use App\Filament\Resources\TreePricePlans\Tables\TreePricePlansTable;
use App\Models\TreePricePlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TreePricePlanResource extends Resource
{
    protected static ?string $model = TreePricePlan::class;

    protected static string|UnitEnum|null $navigationGroup = 'Tree Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'TreePricePlan';

    public static function form(Schema $schema): Schema
    {
        return TreePricePlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TreePricePlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTreePricePlans::route('/'),
            'create' => CreateTreePricePlan::route('/create'),
            'edit' => EditTreePricePlan::route('/{record}/edit'),
        ];
    }
}
