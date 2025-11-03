<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Color;
use App\Models\Planter;
use App\Models\Size;
use App\Models\Variant;

final class PlanterObserver
{
    public function created(Planter $planter): void
    {
        $colors = Color::all();
        $sizes = Size::all();

        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                Variant::firstOrCreate([
                    'size_id' => $size->id,
                    'color_id' => $color->id,
                    'planter_id' => $planter->id,
                ]);
            }
        }
    }
}
