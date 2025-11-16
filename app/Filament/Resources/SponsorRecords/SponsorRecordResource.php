<?php

namespace App\Filament\Resources\SponsorRecords;

use App\Filament\Resources\SponsorRecords\Pages\ListSponsorRecords;
use App\Filament\Resources\SponsorRecords\Schemas\SponsorRecordForm;
use App\Filament\Resources\SponsorRecords\Tables\SponsorRecordsTable;
use App\Models\SponsorRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SponsorRecordResource extends Resource
{
    protected static ?string $model = SponsorRecord::class;

    protected static string|UnitEnum|null $navigationGroup = 'Sponsorships & Adoptions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SponsorRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SponsorRecordsTable::configure($table);
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
            'index' => ListSponsorRecords::route('/'),
        ];
    }
}
