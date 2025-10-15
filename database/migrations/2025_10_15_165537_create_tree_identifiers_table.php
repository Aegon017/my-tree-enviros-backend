<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tree_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tree_instance_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('identifier')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tree_identifiers');
    }
};
