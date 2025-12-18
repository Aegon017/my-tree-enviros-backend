<?php

declare(strict_types=1);

namespace App\Filament\Resources\Initiatives\Pages;

use App\Filament\Resources\Initiatives\InitiativeResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateInitiative extends CreateRecord
{
    protected static string $resource = InitiativeResource::class;
}
