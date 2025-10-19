<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->morphs('orderable');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2);
            $table->decimal('gst_amount', 12, 2);
            $table->decimal('cgst_amount', 12, 2);
            $table->decimal('sgst_amount', 12, 2);
            $table->unsignedBigInteger('shipping_address_id')->nullable();
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->string('status');
            $table->string('currency', 8);
            $table->timestamps();
        });
    }
};
