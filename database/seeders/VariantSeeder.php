<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Variant;
use App\Models\Color;
use App\Models\Size;
use App\Models\Planter;

class VariantSeeder extends Seeder
{
    public function run(): void
    {
        $colors = Color::all();
        $sizes = Size::all();
        $planters = Planter::all();

        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                foreach ($planters as $planter) {
                    Variant::firstOrCreate([
                        'color_id'   => $color->id,
                        'size_id'    => $size->id,
                        'planter_id' => $planter->id
                    ]);
                }
            }
        }
    }
}
