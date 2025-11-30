<?php

declare(strict_types=1);

namespace App\Filament\Resources\Charges;

use App\Filament\Resources\Charges\Pages\CreateCharge;
use App\Filament\Resources\Charges\Pages\EditCharge;
use App\Filament\Resources\Charges\Pages\ListCharges;
use App\Filament\Resources\Charges\Schemas\ChargeForm;
use App\Filament\Resources\Charges\Tables\ChargesTable;
use App\Models\Charge;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class ChargeResource extends Resource
{
    protected static ?string $model = Charge::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Charge';

    public static function form(Schema $schema): Schema
    {
        return ChargeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChargesTable::configure($table);
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
            'index' => ListCharges::route('/'),
            'create' => CreateCharge::route('/create'),
            'edit' => EditCharge::route('/{record}/edit'),
        ];
    }
}
