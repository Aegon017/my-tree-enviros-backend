<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trees;

use App\Filament\Resources\Trees\Pages\CreateTree;
use App\Filament\Resources\Trees\Pages\EditTree;
use App\Filament\Resources\Trees\Pages\ListTrees;
use App\Filament\Resources\Trees\RelationManagers\InstancesRelationManager;
use App\Filament\Resources\Trees\RelationManagers\PlanPricesRelationManager;
use App\Filament\Resources\Trees\RelationManagers\TreeInstancesRelationManager;
use App\Filament\Resources\Trees\RelationManagers\TreeLocationsRelationManager;
use App\Filament\Resources\Trees\Schemas\TreeForm;
use App\Filament\Resources\Trees\Tables\TreesTable;
use App\Models\Tree;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class TreeResource extends Resource
{
    protected static ?string $model = Tree::class;

    protected static string|UnitEnum|null $navigationGroup = 'Tree Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Tree';

    public static function form(Schema $schema): Schema
    {
        return TreeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TreesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // PlanPricesRelationManager::class,
            // TreeInstancesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrees::route('/'),
            'create' => CreateTree::route('/create'),
            'edit' => EditTree::route('/{record}/edit'),
        ];
    }
}
