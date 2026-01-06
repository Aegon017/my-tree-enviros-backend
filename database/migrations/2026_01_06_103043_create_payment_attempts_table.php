<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('attempt_reference')->unique(); // ATT-timestamp-random
            $table->string('payment_method'); // razorpay, phonepe
            $table->string('payment_gateway_order_id')->nullable(); // Razorpay/PhonePe order ID
            $table->string('status')->default('initiated'); // initiated, processing, completed, failed, expired

            // Pricing snapshot
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total_discount', 12, 2)->default(0);
            $table->decimal('total_tax', 12, 2)->default(0);
            $table->decimal('total_shipping', 12, 2)->default(0);
            $table->decimal('total_fee', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->string('currency', 3)->default('INR');

            // References
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->constrained()->nullOnDelete();
            $table->json('shipping_address_snapshot')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // Browser info, IP, etc.
            $table->foreignId('created_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Auto-expire after 24 hours
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('payment_gateway_order_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
