<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('phone');
            $table->string('address');
            $table->string('area');
            $table->string('city');
            $table->string('postal_code', 8);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->string('post_office_name')->nullable();
            $table->string('post_office_branch_type')->nullable();

            $table->boolean('is_default');
            $table->timestamps();
        });
    }
};
