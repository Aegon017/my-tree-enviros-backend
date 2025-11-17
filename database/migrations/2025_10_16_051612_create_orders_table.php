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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method')->nullable();
            $table->string('status', 20);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2);
            $table->decimal('gst_amount', 12, 2);
            $table->decimal('cgst_amount', 12, 2);
            $table->decimal('sgst_amount', 12, 2);
            $table->decimal('total', 10, 2);
            $table->string('reference_number')->unique();
            $table->foreignId('shipping_address_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency', 8);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }
};
