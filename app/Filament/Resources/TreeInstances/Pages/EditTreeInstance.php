<?php

namespace App\Filament\Resources\TreeInstances\Pages;

use App\Filament\Resources\TreeInstances\TreeInstanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTreeInstance extends EditRecord
{
    protected static string $resource = TreeInstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
