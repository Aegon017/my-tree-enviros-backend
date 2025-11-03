<?php

declare(strict_types=1);

namespace App\Filament\Resources\Planters\Pages;

use App\Filament\Resources\Planters\PlanterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListPlanters extends ListRecords
{
    protected static string $resource = PlanterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
