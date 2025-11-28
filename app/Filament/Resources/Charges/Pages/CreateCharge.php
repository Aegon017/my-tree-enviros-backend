<?php

declare(strict_types=1);

namespace App\Filament\Resources\Charges\Pages;

use App\Filament\Resources\Charges\ChargeResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCharge extends CreateRecord
{
    protected static string $resource = ChargeResource::class;
}
