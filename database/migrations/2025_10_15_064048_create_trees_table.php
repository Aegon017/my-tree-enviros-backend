<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trees', function (Blueprint $table) {
            $table->id();
            $table->uuid('sku')->unique();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('age');
            $table->string('age_unit', 8);
            $table->text('description');
            $table->boolean('is_active');
            $table->timestamps();
        });
    }
};
