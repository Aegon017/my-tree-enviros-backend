<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Color;
use App\Models\Inventory;
use App\Models\Planter;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\Variant;
use Illuminate\Database\Seeder;

final class ProductVariantSeeder extends Seeder
{
    public function run(): void
    {
        // Create some colors
        $red = Color::firstOrCreate(['name' => 'Red', 'code' => '#FF0000']);
        $blue = Color::firstOrCreate(['name' => 'Blue', 'code' => '#0000FF']);
        $green = Color::firstOrCreate(['name' => 'Green', 'code' => '#00FF00']);

        // Create some sizes
        $small = Size::firstOrCreate(['name' => 'Small']);
        $medium = Size::firstOrCreate(['name' => 'Medium']);
        $large = Size::firstOrCreate(['name' => 'Large']);

        // Create some planters
        $ceramic = Planter::firstOrCreate(['name' => 'Ceramic Pot']);
        $plastic = Planter::firstOrCreate(['name' => 'Plastic Pot']);

        // Create product category if not exists
        $category = \App\Models\ProductCategory::firstOrCreate([
            'name' => 'Oils',
            'slug' => 'oils',
        ]);

        // Create a test product with variants
        $product = Product::firstOrCreate([
            'product_category_id' => $category->id,
            'name' => 'Organic Neem Oil',
            'slug' => 'organic-neem-oil',
            'botanical_name' => 'Azadirachta indica',
            'nick_name' => 'Neem Oil',
            'base_price' => 500,
            'discount_price' => 0,
            'short_description' => 'Pure organic neem oil extracted from neem seeds',
            'description' => 'High-quality organic neem oil perfect for skin care and pest control.',
            'is_active' => true,
        ]);

        // Create inventory for the product
        $inventory = Inventory::firstOrCreate([
            'product_id' => $product->id,
        ]);

        // Create variants
        $variants = [
            // Small Ceramic Pot - Red
            [
                'color' => $red,
                'size' => $small,
                'planter' => $ceramic,
                'base_price' => 550,
                'discount_price' => 500,
                'stock_quantity' => 25,
                'is_instock' => true,
            ],
            // Small Ceramic Pot - Blue
            [
                'color' => $blue,
                'size' => $small,
                'planter' => $ceramic,
                'base_price' => 550,
                'discount_price' => 500,
                'stock_quantity' => 20,
                'is_instock' => true,
            ],
            // Medium Ceramic Pot - Red
            [
                'color' => $red,
                'size' => $medium,
                'planter' => $ceramic,
                'base_price' => 650,
                'discount_price' => 600,
                'stock_quantity' => 15,
                'is_instock' => true,
            ],
            // Medium Ceramic Pot - Green
            [
                'color' => $green,
                'size' => $medium,
                'planter' => $ceramic,
                'base_price' => 650,
                'discount_price' => 600,
                'stock_quantity' => 12,
                'is_instock' => true,
            ],
            // Large Plastic Pot - Blue
            [
                'color' => $blue,
                'size' => $large,
                'planter' => $plastic,
                'base_price' => 750,
                'discount_price' => 700,
                'stock_quantity' => 8,
                'is_instock' => true,
            ],
            // Out of stock variant
            [
                'color' => $green,
                'size' => $large,
                'planter' => $plastic,
                'base_price' => 750,
                'discount_price' => 700,
                'stock_quantity' => 0,
                'is_instock' => false,
            ],
        ];

        foreach ($variants as $variantData) {
            // Create variant combination
            $variant = Variant::firstOrCreate([
                'color_id' => $variantData['color']->id,
                'size_id' => $variantData['size']->id,
                'planter_id' => $variantData['planter']->id,
            ]);

            // Create product variant
            ProductVariant::firstOrCreate([
                'inventory_id' => $inventory->id,
                'variant_id' => $variant->id,
                'sku' => 'NEM-'.mb_strtoupper($variantData['color']->name[0]).mb_strtoupper($variantData['size']->name[0]).mb_strtoupper($variantData['planter']->name[0]).rand(100, 999),
                'base_price' => $variantData['base_price'],
                'discount_price' => $variantData['discount_price'],
                'stock_quantity' => $variantData['stock_quantity'],
                'is_instock' => $variantData['is_instock'],
            ]);
        }

        $this->command->info('Product variants seeded successfully!');
    }
}
