<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->json('item_snapshot')->nullable()->after('sponsor_quantity');
            $table->string('item_name')->nullable()->after('item_snapshot');
            $table->decimal('unit_price', 12, 2)->nullable()->after('item_name');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('unit_price');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'item_snapshot',
                'item_name',
                'unit_price',
                'discount_amount',
                'tax_amount',
            ]);
        });
    }
};
