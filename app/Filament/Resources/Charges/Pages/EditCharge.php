<?php

declare(strict_types=1);

namespace App\Filament\Resources\Charges\Pages;

use App\Filament\Resources\Charges\ChargeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditCharge extends EditRecord
{
    protected static string $resource = ChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
