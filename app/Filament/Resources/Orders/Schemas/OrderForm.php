<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatusEnum;
use App\Enums\TreeTypeEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')
                    ->disabled(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('type')
                    ->options(TreeTypeEnum::class)
                    ->required(),
                Select::make('status')
                    ->options(OrderStatusEnum::class)
                    ->required(),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
            ]);
    }
}
