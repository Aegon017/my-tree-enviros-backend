<?php

declare(strict_types=1);

namespace App\Filament\Resources\TreeUpdates\Pages;

use App\Filament\Resources\TreeUpdates\TreeUpdateResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTreeUpdate extends CreateRecord
{
    protected static string $resource = TreeUpdateResource::class;
}
