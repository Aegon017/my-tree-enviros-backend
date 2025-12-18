<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppSettings\Pages;

use App\Filament\Resources\AppSettings\AppSettingResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateAppSetting extends CreateRecord
{
    protected static string $resource = AppSettingResource::class;
}
