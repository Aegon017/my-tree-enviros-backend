<?php

declare(strict_types=1);

namespace App\Filament\Resources\Initiatives;

use App\Filament\Resources\Initiatives\Pages\CreateInitiative;
use App\Filament\Resources\Initiatives\Pages\EditInitiative;
use App\Filament\Resources\Initiatives\Pages\ListInitiatives;
use App\Filament\Resources\Initiatives\RelationManagers\SitesRelationManager;
use App\Filament\Resources\Initiatives\Schemas\InitiativeForm;
use App\Filament\Resources\Initiatives\Tables\InitiativesTable;
use App\Models\Initiative;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class InitiativeResource extends Resource
{
    protected static ?string $model = Initiative::class;

    protected static UnitEnum|string|null $navigationGroup = 'Campaigns';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    public static function form(Schema $schema): Schema
    {
        return InitiativeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InitiativesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SitesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInitiatives::route('/'),
            'create' => CreateInitiative::route('/create'),
            'edit' => EditInitiative::route('/{record}/edit'),
        ];
    }
}
