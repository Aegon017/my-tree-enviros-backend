<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tree_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tree_instance_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();
        });
    }
};
