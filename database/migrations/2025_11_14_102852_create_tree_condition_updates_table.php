<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tree_condition_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tree_instance_id')->constrained()->cascadeOnDelete();
            $table->string('condition');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
};
