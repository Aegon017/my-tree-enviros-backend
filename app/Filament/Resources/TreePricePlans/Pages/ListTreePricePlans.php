<?php

declare(strict_types=1);

namespace App\Filament\Resources\TreePricePlans\Pages;

use App\Filament\Resources\TreePricePlans\TreePricePlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListTreePricePlans extends ListRecords
{
    protected static string $resource = TreePricePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
