<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\CampaignResource\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

final class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Ensure a slug is present before saving updates.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = Str::slug((string) $data['name']);
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Campaign updated';
    }
}
