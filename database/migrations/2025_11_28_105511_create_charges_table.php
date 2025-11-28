<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label', 120);
            $table->string('type', 50);
            $table->string('mode', 50)->default('fixed');
            $table->decimal('value', 12, 4)->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
