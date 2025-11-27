<?php

declare(strict_types=1);

namespace App\Filament\Resources\SponsorRecords\Pages;

use App\Filament\Resources\SponsorRecords\SponsorRecordResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSponsorRecord extends CreateRecord
{
    protected static string $resource = SponsorRecordResource::class;
}
