<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Variant;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('en_IN');

        $plants = [
            ['Areca Palm', 'Butterfly Palm', 'Dypsis lutescens'],
            ['Snake Plant', 'Mother-in-law\'s Tongue', 'Sansevieria trifasciata'],
            ['Peace Lily', 'Snowflower', 'Spathiphyllum wallisii'],
            ['Money Plant', 'Pothos', 'Epipremnum aureum'],
            ['Rubber Plant', 'Ficus Rubber', 'Ficus elastica'],
            ['ZZ Plant', 'Zanzibar Gem', 'Zamioculcas zamiifolia'],
            ['Aloe Vera', 'Aloe', 'Aloe barbadensis miller'],
            ['Chinese Evergreen', 'Aglaonema', 'Aglaonema commutatum'],
            ['Boston Fern', 'Sword Fern', 'Nephrolepis exaltata'],
            ['Spider Plant', 'Airplane Plant', 'Chlorophytum comosum'],
            ['Bamboo Palm', 'Reed Palm', 'Chamaedorea seifrizii'],
            ['Ponytail Palm', 'Elephant Foot Tree', 'Beaucarnea recurvata'],
            ['Croton', 'Garden Croton', 'Codiaeum variegatum'],
            ['Jade Plant', 'Crassula', 'Crassula ovata'],
            ['Fiddle Leaf Fig', 'Ficus lyrata', 'Ficus lyrata'],
            ['Philodendron', 'Philodendron', 'Philodendron hederaceum'],
            ['Pothos Golden', 'Golden Money Plant', 'Epipremnum aureum'],
            ['Syngonium', 'Arrowhead Plant', 'Syngonium podophyllum'],
            ['Peace Lily Compacta', 'Compact Peace Lily', 'Spathiphyllum wallisii mini'],
            ['Anthurium', 'Flamingo Lily', 'Anthurium andraeanum'],
            ['Bonsai Ficus', 'Ficus Bonsai', 'Ficus retusa'],
            ['Haworthia', 'Zebra Plant', 'Haworthia attenuata'],
            ['Cactus Mix', 'Mini Cactus', 'Cactaceae'],
            ['Succulent Mix', 'Mini Succulent', 'Crassula species'],
            ['Agave', 'Agave', 'Agave americana'],
            ['Calathea', 'Prayer Plant', 'Calathea makoyana'],
            ['Areca Mini', 'Mini Areca', 'Dypsis lutescens dwarf'],
            ['Rubber Burgundy', 'Burgundy Rubber', 'Ficus elastica burgundy'],
            ['Philodendron Birkin', 'Birkin', 'Philodendron Birkin'],
            ['Dieffenbachia', 'Dumb Cane', 'Dieffenbachia seguine'],
        ];

        foreach ($plants as $p) {
            $product = Product::create([
                'name' => $p[0],
                'slug' => Str::slug($p[0]),
                'botanical_name' => $p[2],
                'nick_name' => $p[1],
                'short_description' => $faker->sentence(10),
                'description' => $faker->paragraph(4),
                'product_category_id' => ProductCategory::inRandomOrder()->value('id'),
                'is_active' => true,
            ]);

            $inventory = Inventory::create([
                'product_id' => $product->id,
            ]);

            $variants = Variant::inRandomOrder()->take(rand(2, 4))->get();

            foreach ($variants as $v) {
                ProductVariant::create([
                    'inventory_id' => $inventory->id,
                    'variant_id' => $v->id,
                    'base_price' => $faker->numberBetween(300, 2000),
                    'discount_price' => $faker->boolean(40) ? $faker->numberBetween(250, 1500) : null,
                    'stock_quantity' => $faker->numberBetween(5, 50),
                    'is_instock' => true,
                ]);
            }

            $seedImages = glob(public_path('seed-images/*.jpg'));
            if ($seedImages) {
                foreach (array_slice($seedImages, 0, 2) as $img) {
                    $product->addMedia($img)->preservingOriginal()->toMediaCollection('images');
                }
            }
        }
    }
}