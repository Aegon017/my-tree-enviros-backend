<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdoptRecords;

use App\Filament\Resources\AdoptRecords\Pages\ListAdoptRecords;
use App\Filament\Resources\AdoptRecords\Schemas\AdoptRecordForm;
use App\Filament\Resources\AdoptRecords\Tables\AdoptRecordsTable;
use App\Models\AdoptRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class AdoptRecordResource extends Resource
{
    protected static ?string $model = AdoptRecord::class;

    protected static string|UnitEnum|null $navigationGroup = 'Sponsorships & Adoptions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AdoptRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdoptRecordsTable::configure($table);
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
            'index' => ListAdoptRecords::route('/'),
        ];
    }
}
