<?php

namespace App\Filament\Resources\AdoptRecords\Pages;

use App\Filament\Resources\AdoptRecords\AdoptRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdoptRecord extends EditRecord
{
    protected static string $resource = AdoptRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
