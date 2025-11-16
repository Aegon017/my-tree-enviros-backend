<?php

namespace App\Filament\Resources\TreeInstances\Pages;

use App\Filament\Resources\TreeInstances\TreeInstanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTreeInstances extends ListRecords
{
    protected static string $resource = TreeInstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
