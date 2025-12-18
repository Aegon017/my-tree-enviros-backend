<?php

declare(strict_types=1);

namespace App\Filament\Resources\Initiatives\Pages;

use App\Filament\Resources\Initiatives\InitiativeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditInitiative extends EditRecord
{
    protected static string $resource = InitiativeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
