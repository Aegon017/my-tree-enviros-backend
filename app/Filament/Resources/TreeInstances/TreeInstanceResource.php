<?php

namespace App\Filament\Resources\TreeInstances;

use App\Filament\Resources\TreeInstances\Pages\CreateTreeInstance;
use App\Filament\Resources\TreeInstances\Pages\EditTreeInstance;
use App\Filament\Resources\TreeInstances\Pages\ListTreeInstances;
use App\Filament\Resources\TreeInstances\Schemas\TreeInstanceForm;
use App\Filament\Resources\TreeInstances\Tables\TreeInstancesTable;
use App\Models\TreeInstance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TreeInstanceResource extends Resource
{
    protected static ?string $model = TreeInstance::class;

    protected static string|UnitEnum|null $navigationGroup = 'Tree Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TreeInstanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TreeInstancesTable::configure($table);
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
            'index' => ListTreeInstances::route('/'),
            'create' => CreateTreeInstance::route('/create'),
            'edit' => EditTreeInstance::route('/{record}/edit'),
        ];
    }
}
