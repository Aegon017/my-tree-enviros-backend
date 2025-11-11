<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('botanical_name');
            $table->string('nick_name');
            $table->string('short_description');
            $table->text('description');
            $table->decimal('selling_price', 10, 2)->nullable()->after('description');
            $table->decimal('original_price', 10, 2)->nullable()->after('selling_price');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
