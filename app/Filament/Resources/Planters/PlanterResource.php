<?php

declare(strict_types=1);

namespace App\Filament\Resources\Planters;

use App\Filament\Resources\Planters\Pages\CreatePlanter;
use App\Filament\Resources\Planters\Pages\EditPlanter;
use App\Filament\Resources\Planters\Pages\ListPlanters;
use App\Filament\Resources\Planters\Schemas\PlanterForm;
use App\Filament\Resources\Planters\Tables\PlantersTable;
use App\Models\Planter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class PlanterResource extends Resource
{
    protected static ?string $model = Planter::class;

    protected static UnitEnum|string|null $navigationGroup = 'E-commerce';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PlanterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlantersTable::configure($table);
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
            'index' => ListPlanters::route('/'),
            'create' => CreatePlanter::route('/create'),
            'edit' => EditPlanter::route('/{record}/edit'),
        ];
    }
}
