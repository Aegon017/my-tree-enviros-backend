<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sliders\Pages;

use App\Filament\Resources\Sliders\SliderResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSlider extends CreateRecord
{
    protected static string $resource = SliderResource::class;
}
