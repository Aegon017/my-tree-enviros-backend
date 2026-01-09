<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_token', 64)->unique();
            $table->string('status', 20)->default('active');

            $table->json('items');
            $table->json('pricing');

            $table->foreignId('shipping_address_id')->nullable()->constrained('shipping_addresses');
            $table->json('shipping_address_snapshot')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('currency', 3)->default('INR');

            $table->foreignId('coupon_id')->nullable()->constrained('coupons');
            $table->string('coupon_code', 50)->nullable();
            $table->decimal('coupon_discount', 12, 2)->default(0);

            $table->string('gateway_order_id')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('session_token');
            $table->index('gateway_order_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_sessions');
    }
};
