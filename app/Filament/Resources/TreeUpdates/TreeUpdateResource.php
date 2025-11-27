<?php

declare(strict_types=1);

namespace App\Filament\Resources\TreeUpdates;

use App\Filament\Resources\TreeUpdates\Pages\CreateTreeUpdate;
use App\Filament\Resources\TreeUpdates\Pages\EditTreeUpdate;
use App\Filament\Resources\TreeUpdates\Pages\ListTreeUpdates;
use App\Filament\Resources\TreeUpdates\Schemas\TreeUpdateForm;
use App\Filament\Resources\TreeUpdates\Tables\TreeUpdatesTable;
use App\Models\TreeUpdate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class TreeUpdateResource extends Resource
{
    protected static ?string $model = TreeUpdate::class;

    protected static string|UnitEnum|null $navigationGroup = 'Tree Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TreeUpdateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TreeUpdatesTable::configure($table);
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
            'index' => ListTreeUpdates::route('/'),
            'create' => CreateTreeUpdate::route('/create'),
            'edit' => EditTreeUpdate::route('/{record}/edit'),
        ];
    }
}
