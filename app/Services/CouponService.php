<?php

declare(strict_types=1);

namespace App\Services\Coupons;

use App\Models\Coupon;
use Illuminate\Support\Facades\DB;

final class CouponService
{
    public function validateAndCalculate(?string $code, float $subtotal): ?array
    {
        if (! $code) {
            return null;
        }

        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->is_enabled) {
            return null;
        }

        if ($coupon->expires_at && now()->gt($coupon->expires_at)) {
            return null;
        }

        if ($coupon->quantity !== null && $coupon->quantity <= 0) {
            return null;
        }

        if ($coupon->limit !== null) {
            $usedCount = DB::table(config('couponables.pivot_table', 'couponables'))
                ->where('coupon_id', $coupon->id)
                ->count();

            if ($usedCount >= $coupon->limit) {
                return null;
            }
        }

        $discount = $this->calculateDiscount($coupon, $subtotal);

        return [
            'coupon' => $coupon,
            'discount' => $discount,
        ];
    }

    public function markRedeemed(int $couponId, int $orderId): void
    {
        DB::table(config('couponables.pivot_table', 'couponables'))->insert([
            'coupon_id' => $couponId,
            'couponable_type' => config('couponables.couponable_model', \App\Models\Order::class),
            'couponable_id' => $orderId,
            'redeemed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function calculateDiscount(Coupon $coupon, float $subtotal): float
    {
        $data = $coupon->data ?? [];

        $discount = $coupon->type === 'percentage' ? $subtotal * ((float) $coupon->value / 100) : (float) $coupon->value;

        if (isset($data['max_discount'])) {
            $discount = min($discount, (float) $data['max_discount']);
        }

        if ($discount < 0) {
            return 0;
        }

        return $discount;
    }
}
