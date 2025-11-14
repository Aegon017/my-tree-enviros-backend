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
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tree_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamps();
        });
    }
};
