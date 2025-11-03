<?php

declare(strict_types=1);

namespace App\Filament\Resources\Planters\Pages;

use App\Filament\Resources\Planters\PlanterResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePlanter extends CreateRecord
{
    protected static string $resource = PlanterResource::class;
}
