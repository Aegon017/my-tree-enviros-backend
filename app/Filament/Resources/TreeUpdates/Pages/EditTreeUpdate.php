<?php

declare(strict_types=1);

namespace App\Filament\Resources\TreeUpdates\Pages;

use App\Filament\Resources\TreeUpdates\TreeUpdateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditTreeUpdate extends EditRecord
{
    protected static string $resource = TreeUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
