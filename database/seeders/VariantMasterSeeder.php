<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Color;
use App\Models\Size;
use App\Models\Planter;

class VariantMasterSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['name' => 'Green', 'code' => '#2e7d32'],
            ['name' => 'Dark Green', 'code' => '#1b5e20'],
            ['name' => 'Golden Yellow', 'code' => '#f9a825'],
            ['name' => 'Brown', 'code' => '#6d4c41'],
        ];

        $sizes = [
            ['name' => 'Small', 'code' => 'S'],
            ['name' => 'Medium', 'code' => 'M'],
            ['name' => 'Large', 'code' => 'L'],
        ];

        $planters = [
            ['name' => 'Plastic Pot'],
            ['name' => 'Ceramic Pot'],
            ['name' => 'Clay Pot'],
            ['name' => 'Decorative Pot'],
        ];

        foreach ($colors as $c) Color::firstOrCreate(['name' => $c['name']], $c);
        foreach ($sizes as $s) Size::firstOrCreate(['name' => $s['name']], $s);
        foreach ($planters as $p) Planter::firstOrCreate(['name' => $p['name']]);
    }
}