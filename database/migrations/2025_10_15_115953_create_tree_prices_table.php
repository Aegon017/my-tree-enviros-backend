<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tree_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tree_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 8, 2);
            $table->unsignedTinyInteger('duration');
            $table->string('duration_type');
            $table->boolean('is_active');
            $table->timestamps();
        });
    }
};
