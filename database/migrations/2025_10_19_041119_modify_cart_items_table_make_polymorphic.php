<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Drop existing foreign keys and columns
            $table->dropForeign(['tree_instance_id']);
            $table->dropForeign(['tree_plan_price_id']);
            $table->dropUnique('cart_tree_plan_unique');

            $table->dropColumn(['tree_instance_id', 'tree_plan_price_id']);

            // Add polymorphic columns
            $table->morphs('cartable');

            // Add additional fields for variants and options
            $table->json('options')->nullable()->after('price');

            // Update unique constraint
            $table->unique(['cart_id', 'cartable_type', 'cartable_id'], 'cart_item_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Drop polymorphic columns
            $table->dropMorphs('cartable');
            $table->dropUnique('cart_item_unique');
            $table->dropColumn('options');

            // Restore original columns
            $table->foreignId('tree_instance_id')->after('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tree_plan_price_id')->after('tree_instance_id')->constrained()->cascadeOnDelete();

            $table->unique(['cart_id', 'tree_instance_id', 'tree_plan_price_id'], 'cart_tree_plan_unique');
        });
    }
};
