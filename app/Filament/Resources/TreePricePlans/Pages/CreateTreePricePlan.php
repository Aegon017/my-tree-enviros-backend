<?php

declare(strict_types=1);

namespace App\Filament\Resources\TreePricePlans\Pages;

use App\Filament\Resources\TreePricePlans\TreePricePlanResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTreePricePlan extends CreateRecord
{
    protected static string $resource = TreePricePlanResource::class;
}
