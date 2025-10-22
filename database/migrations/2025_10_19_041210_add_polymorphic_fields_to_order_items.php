<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Add polymorphic columns for generic item support
            $table->string('orderable_type')->nullable()->after('order_id');
            $table->unsignedBigInteger('orderable_id')->nullable()->after('orderable_type');
            $table->index(['orderable_type', 'orderable_id']);

            // Add quantity field for products
            $table->unsignedInteger('quantity')->default(1)->after('tree_plan_price_id');

            // Add options field for storing variant details, plan info, etc.
            $table->json('options')->nullable()->after('is_renewal');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Drop new columns
            $table->dropIndex(['orderable_type', 'orderable_id']);
            $table->dropColumn(['orderable_type', 'orderable_id', 'quantity', 'options']);
        });
    }
};
