<?php

namespace App\Filament\Resources\Planters\Pages;

use App\Filament\Resources\Planters\PlanterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlanter extends EditRecord
{
    protected static string $resource = PlanterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
