<?php

namespace App\Filament\Resources\TreeUpdates\Pages;

use App\Filament\Resources\TreeUpdates\TreeUpdateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTreeUpdate extends EditRecord
{
    protected static string $resource = TreeUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
