<?php

namespace App\Filament\Resources\SponsorRecords\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SponsorRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_item_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('tree_id')
                    ->required()
                    ->numeric(),
                TextInput::make('plan_id')
                    ->required()
                    ->numeric(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric(),
                DatePicker::make('start_date')
                    ->required(),
                DatePicker::make('end_date')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
