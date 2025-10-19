<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tree_price_plans', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('type', 13);
            $table->unsignedTinyInteger('duration');
            $table->string('duration_type', 13);
            $table->json('features')->nullable();
            $table->boolean('is_active');
            $table->timestamps();

            $table->unique(['name', 'type']);
        });
    }
};
