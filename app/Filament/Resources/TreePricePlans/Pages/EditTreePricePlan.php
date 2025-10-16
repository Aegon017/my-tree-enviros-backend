<?php

namespace App\Filament\Resources\TreePricePlans\Pages;

use App\Filament\Resources\TreePricePlans\TreePricePlanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTreePricePlan extends EditRecord
{
    protected static string $resource = TreePricePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
