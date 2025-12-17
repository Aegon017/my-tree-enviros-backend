<?php

namespace App\Filament\Resources\PaymentGateways;

use App\Filament\Resources\PaymentGateways\Pages\CreatePaymentGateway;
use App\Filament\Resources\PaymentGateways\Pages\EditPaymentGateway;
use App\Filament\Resources\PaymentGateways\Pages\ListPaymentGateways;
use App\Filament\Resources\PaymentGateways\Schemas\PaymentGatewayForm;
use App\Filament\Resources\PaymentGateways\Tables\PaymentGatewaysTable;
use App\Models\PaymentGateway;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentGatewayResource extends Resource
{
    protected static ?string $model = PaymentGateway::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PaymentGatewayForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentGatewaysTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentGateways::route('/'),
            'create' => CreatePaymentGateway::route('/create'),
            'edit' => EditPaymentGateway::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }
}
