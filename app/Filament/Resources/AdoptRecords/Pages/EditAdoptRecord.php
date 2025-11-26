<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdoptRecords\Pages;

use App\Filament\Resources\AdoptRecords\AdoptRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditAdoptRecord extends EditRecord
{
    protected static string $resource = AdoptRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
