<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_attempt_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50); // product, sponsor, adopt, campaign

            // Foreign keys (same as order_items)
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tree_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_price_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tree_instance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('initiative_site_id')->nullable()->constrained()->nullOnDelete();

            // Quantities and amounts
            $table->unsignedInteger('sponsor_quantity')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('amount', 12, 2);
            $table->decimal('total_amount', 12, 2);

            // Snapshot (captured at attempt creation)
            $table->json('item_snapshot');
            $table->string('item_name');
            $table->decimal('unit_price', 12, 2);

            // Dedication
            $table->json('dedication')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempt_items');
    }
};
