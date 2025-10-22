<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tree_instance_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('tree_plan_price_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('gst_amount', 10, 2)->default(0.00);
            $table->decimal('cgst_amount', 10, 2)->default(0.00);
            $table->decimal('sgst_amount', 10, 2)->default(0.00);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('is_renewal');
            $table->timestamps();
        });
    }
};
