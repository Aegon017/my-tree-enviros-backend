<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdoptRecords\Pages;

use App\Filament\Resources\AdoptRecords\AdoptRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAdoptRecords extends ListRecords
{
    protected static string $resource = AdoptRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
