<?php

declare(strict_types=1);

namespace App\Filament\Resources\TreeUpdates\Pages;

use App\Filament\Resources\TreeUpdates\TreeUpdateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListTreeUpdates extends ListRecords
{
    protected static string $resource = TreeUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
