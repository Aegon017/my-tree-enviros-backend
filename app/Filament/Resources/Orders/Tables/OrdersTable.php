<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatusEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;
use App\Models\User;
use Illuminate\Support\Facades\Notification as FacadeNotification;

final class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', direction: 'desc')
            ->columns([
                TextColumn::make('reference_number')->searchable(),
                TextColumn::make('user.name')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('items.type')->label('Item Types')->listWithLineBreaks()->separator(', '),
                TextColumn::make('grand_total'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([])
            ->recordActions([
                ActionGroup::make([
                    MediaAction::make('invoice')
                        ->icon(Heroicon::OutlinedArrowDownOnSquareStack)
                        ->label('Invoice')
                        ->media(fn($record): string => route('admin.orders.invoice', $record))
                        ->mediaType(MediaAction::TYPE_PDF)
                        ->mediaType(MediaAction::TYPE_PDF)
                        ->visible(fn($record): bool => $record->status === OrderStatusEnum::PAID || $record->status === OrderStatusEnum::SHIPPED || $record->status === OrderStatusEnum::OUT_FOR_DELIVERY || $record->status === OrderStatusEnum::DELIVERED || $record->status === OrderStatusEnum::COMPLETED),

                    Action::make('complete')
                        ->label('Mark as Completed')
                        ->icon(Heroicon::OutlinedCheckBadge)
                        ->color('success')
                        ->visible(fn($record): bool => $record->status === OrderStatusEnum::PAID && ! $record->isShippable())
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'status' => OrderStatusEnum::COMPLETED,
                            ]);

                            \Filament\Notifications\Notification::make()->title('Order Completed')->success()->send();
                        }),

                    Action::make('ship')
                        ->label('Mark as Shipped')
                        ->icon(Heroicon::OutlinedTruck)
                        ->color('info')
                        ->visible(fn($record): bool => $record->status === OrderStatusEnum::PAID && $record->isShippable())
                        ->form([
                            \Filament\Forms\Components\TextInput::make('courier_name')->required(),
                            \Filament\Forms\Components\TextInput::make('tracking_id')->required(),
                        ])
                        ->action(function (Table $table, $record, array $data) {
                            $record->update([
                                'status' => OrderStatusEnum::SHIPPED,
                                'courier_name' => $data['courier_name'],
                                'tracking_id' => $data['tracking_id'],
                                'shipped_at' => now(),
                            ]);

                            $record->user->notify(new \App\Notifications\OrderShippedNotification($record));

                            $admins = User::role('super_admin')->get();
                            if ($admins->isNotEmpty()) {
                                FacadeNotification::send($admins, new \App\Notifications\OrderShippedNotification($record));
                            }

                            \Filament\Notifications\Notification::make()->title('Order Shipped')->success()->send();
                        }),

                    Action::make('out_for_delivery')
                        ->label('Mark Out for Delivery')
                        ->icon(Heroicon::OutlinedMap)
                        ->color('warning')
                        ->visible(fn($record): bool => $record->status === OrderStatusEnum::SHIPPED && $record->isShippable())
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['status' => OrderStatusEnum::OUT_FOR_DELIVERY]);
                            // Optional: Notification for Out For Delivery is not explicitly requested as Mail, maybe just DB? 
                            // For now skipping explicit mail to avoid spam, or can add if requested.
                            \Filament\Notifications\Notification::make()->title('Order Out for Delivery')->success()->send();
                        }),

                    Action::make('deliver')
                        ->label('Mark as Delivered')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->visible(fn($record): bool => $record->status === OrderStatusEnum::OUT_FOR_DELIVERY && $record->isShippable())
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'status' => OrderStatusEnum::DELIVERED,
                                'delivered_at' => now(),
                            ]);

                            $record->user->notify(new \App\Notifications\OrderDeliveredNotification($record));

                            $admins = User::role('super_admin')->get();
                            if ($admins->isNotEmpty()) {
                                FacadeNotification::send($admins, new \App\Notifications\OrderDeliveredNotification($record));
                            }

                            \Filament\Notifications\Notification::make()->title('Order Delivered')->success()->send();
                        }),
                ]),
            ], position: RecordActionsPosition::BeforeCells);
    }
}
