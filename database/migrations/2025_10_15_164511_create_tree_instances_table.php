<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tree_instances', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->foreignId('tree_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20);
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('age_unit', 8);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('planted_at')->nullable();
            $table->timestamps();
        });
    }
};
