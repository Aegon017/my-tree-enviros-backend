<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\CampaignResource\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
