<?php

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
        Schema::create('sponsor_record_tree_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tree_instance_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsor_record_tree_instances');
    }
};
