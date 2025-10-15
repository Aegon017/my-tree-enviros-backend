<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trees\Pages;

use App\Filament\Resources\Trees\TreeResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTree extends CreateRecord
{
    protected static string $resource = TreeResource::class;
}
