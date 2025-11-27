<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdoptRecords\Pages;

use App\Filament\Resources\AdoptRecords\AdoptRecordResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateAdoptRecord extends CreateRecord
{
    protected static string $resource = AdoptRecordResource::class;
}
