<?php

namespace App\Observers;

use App\Models\Color;
use App\Models\Planter;
use App\Models\Size;
use App\Models\Variant;

class ColorObserver
{
    public function created(Color $color): void
    {
        $sizes = Size::all();
        $planters = Planter::all();

        foreach ($sizes as $size) {
            foreach ($planters as $planter) {
                Variant::firstOrCreate([
                    'size_id' => $size->id,
                    'color_id' => $color->id,
                    'planter_id' => $planter->id,
                ]);
            }
        }
    }
}
