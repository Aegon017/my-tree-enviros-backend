<?php

namespace App\Filament\Resources\AdoptRecords\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AdoptRecordForm
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
                TextInput::make('tree_instance_id')
                    ->required()
                    ->numeric(),
                TextInput::make('plan_id')
                    ->required()
                    ->numeric(),
                DatePicker::make('adopt_start')
                    ->required(),
                DatePicker::make('adopt_end')
                    ->required(),
                TextInput::make('status')
                    ->required(),
            ]);
    }
}
