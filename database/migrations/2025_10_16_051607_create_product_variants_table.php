<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->unique();
            $table->decimal('original_price', 10, 2);
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->integer('stock_quantity');
            $table->boolean('is_instock')->default(true);
            $table->timestamps();
        });
    }
};
