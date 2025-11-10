<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Indoor Plants',
            'Outdoor Plants',
            'Flowering Plants',
            'Succulents & Cactus',
            'Air Purifying Plants',
            'Medicinal Plants',
            'Bonsai Plants',
        ];

        foreach ($categories as $name) {
            ProductCategory::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
