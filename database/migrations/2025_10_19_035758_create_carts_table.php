<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('session_id')
                ->nullable()
                ->index();

            // Restoring original column
            $table->string('type', 10)->default('default');

            $table->string('status', 30)
                ->default('active')
                ->index();

            $table->timestamps();

            $table->unique(['user_id', 'status']);
            $table->unique(['session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
