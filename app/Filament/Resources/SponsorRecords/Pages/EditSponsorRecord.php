<?php

namespace App\Filament\Resources\SponsorRecords\Pages;

use App\Filament\Resources\SponsorRecords\SponsorRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSponsorRecord extends EditRecord
{
    protected static string $resource = SponsorRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
