<?php

declare(strict_types=1);

namespace App\Filament\Resources\Inventories\Pages;

use App\Filament\Resources\Inventories\InventoryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateInventory extends CreateRecord
{
    protected static string $resource = InventoryResource::class;
}
