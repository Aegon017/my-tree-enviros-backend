<?php

declare(strict_types=1);

namespace App\Filament\Resources\SponsorRecords\Pages;

use App\Filament\Resources\SponsorRecords\SponsorRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListSponsorRecords extends ListRecords
{
    protected static string $resource = SponsorRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
