<?php

namespace App\Filament\Resources\AdoptRecords\Pages;

use App\Filament\Resources\AdoptRecords\AdoptRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdoptRecords extends ListRecords
{
    protected static string $resource = AdoptRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
