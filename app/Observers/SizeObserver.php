<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Color;
use App\Models\Planter;
use App\Models\Size;
use App\Models\Variant;

final class SizeObserver
{
    public function created(Size $size): void
    {
        $colors = Color::all();
        $planters = Planter::all();

        foreach ($colors as $color) {
            foreach ($planters as $planter) {
                Variant::create([
                    'size_id' => $size->id,
                    'color_id' => $color->id,
                    'planter_id' => $planter->id,
                ]);
            }
        }
    }
}
