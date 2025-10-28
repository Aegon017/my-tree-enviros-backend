<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\CampaignResource\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    /**
     * Ensure a slug is generated if not provided.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = Str::slug((string) $data['name']);
        }

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Campaign created';
    }
}
