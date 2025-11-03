<?php

declare(strict_types=1);

namespace App\Filament\Resources\Planters\Pages;

use App\Filament\Resources\Planters\PlanterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditPlanter extends EditRecord
{
    protected static string $resource = PlanterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
