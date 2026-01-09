<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempt_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charge_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('label');
            $table->decimal('amount', 12, 2);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
};
