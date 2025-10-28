<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatusEnum;
use App\Enums\OrderTypeEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use App\Models\User;

final class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')
                    ->disabled(),
                Select::make('user_id')
                    ->options(function () {
                        return User::query()
                            ->get()
                            ->mapWithKeys(fn($u) => [$u->id => $u->name ?? $u->email ?? ('User #' . $u->id)])
                            ->toArray();
                    })
                    ->searchable()
                    ->required(),
                Select::make('type')
                    ->options(OrderTypeEnum::class)
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
