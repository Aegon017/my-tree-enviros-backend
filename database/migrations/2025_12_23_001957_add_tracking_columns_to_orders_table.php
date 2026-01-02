<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('courier_name')->nullable()->after('cancellation_reason');
            $table->string('tracking_id')->nullable()->after('courier_name');
            $table->timestamp('shipped_at')->nullable()->after('tracking_id');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
        });
    }
};
