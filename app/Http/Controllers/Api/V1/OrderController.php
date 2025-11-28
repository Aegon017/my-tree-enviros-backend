<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Charge;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderCharge;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Services\InvoiceService;
use App\Traits\ResponseHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class OrderController extends Controller
{
    use ResponseHelpers;

    public function __construct(private InvoiceService $invoiceService) {}

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $user = $request->user();
            $items = collect($request->items);
            $subtotal = $items->sum(fn ($item): int|float => $item['quantity'] * $item['amount']);

            $couponResult = $this->validateCoupon($request->coupon_code, $subtotal);
            $totalDiscount = $couponResult['discount'] ?? 0;
            $couponId = $couponResult['coupon']->id ?? null;

            $charges = Charge::where('is_active', true)->get();
            $chargeResults = $this->calculateCharges($charges, $subtotal, $subtotal - $totalDiscount);

            $order = Order::create([
                'user_id' => $user->id,
                'reference_number' => 'ORD-'.time().'-'.random_int(1000, 9999),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'total_discount' => $totalDiscount,
                'total_tax' => $chargeResults['tax'],
                'total_shipping' => $chargeResults['shipping'],
                'total_fee' => $chargeResults['fee'],
                'grand_total' => $chargeResults['total'],
                'coupon_id' => $couponId,
                'payment_method' => $request->payment['method'] ?? null,
                'currency' => 'INR',
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'type' => $item['type'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'campaign_id' => $item['campaign_id'] ?? null,
                    'tree_id' => $item['tree_id'] ?? null,
                    'plan_id' => $item['plan_id'] ?? null,
                    'plan_price_id' => $item['plan_price_id'] ?? null,
                    'tree_instance_id' => $item['tree_instance_id'] ?? null,
                    'sponsor_quantity' => $item['sponsor_quantity'] ?? null,
                    'quantity' => $item['quantity'],
                    'amount' => $item['amount'],
                    'total_amount' => $item['quantity'] * $item['amount'],
                ]);
            }

            foreach ($chargeResults['applied'] as $c) {
                OrderCharge::create([
                    'order_id' => $order->id,
                    'charge_id' => $c['charge_id'],
                    'type' => $c['type'],
                    'label' => $c['label'],
                    'amount' => $c['amount'],
                    'meta' => $c['meta'],
                ]);
            }

            if ($request->payment) {
                $payment = $request->payment;

                OrderPayment::create([
                    'order_id' => $order->id,
                    'amount' => $chargeResults['total'],
                    'payment_method' => $payment['method'],
                    'transaction_id' => $payment['transaction_id'] ?? null,
                    'status' => $payment['status'] ?? 'pending',
                    'paid_at' => ($payment['status'] ?? null) === 'paid' ? now() : null,
                ]);

                if (($payment['status'] ?? null) === 'paid') {
                    $order->update(['status' => 'paid', 'paid_at' => now()]);
                }
            }

            if ($couponId) {
                DB::table(config('couponables.pivot_table', 'couponables'))->insert([
                    'coupon_id' => $couponId,
                    'couponable_type' => Order::class,
                    'couponable_id' => $order->id,
                    'redeemed_at' => now(),
                ]);
            }

            return response()->json([
                'order_id' => $order->id,
                'reference_number' => $order->reference_number,
                'grand_total' => $order->grand_total,
            ]);
        });
    }

    private function validateCoupon(?string $code, float $subtotal): ?array
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

        $discount = $this->calculateCouponDiscount($coupon, $subtotal);

        return [
            'coupon' => $coupon,
            'discount' => $discount,
        ];
    }

    private function calculateCouponDiscount(Coupon $coupon, float $subtotal): float
    {
        $discount = $coupon->type === 'percentage' ? $subtotal * ($coupon->value / 100) : (float) $coupon->value;

        if (isset($coupon->data['max_discount'])) {
            $discount = min($discount, $coupon->data['max_discount']);
        }

        if ($discount < 0) {
            return 0;
        }

        return $discount;
    }

    private function calculateCharges($charges, float $subtotal, float $afterDiscount): array
    {
        $totalTax = 0;
        $totalShipping = 0;
        $totalFee = 0;
        $applied = [];

        foreach ($charges as $charge) {
            $base = $charge->type === 'tax' ? $afterDiscount : $subtotal;

            $amount = $charge->mode === 'percentage'
                ? $base * ($charge->value / 100)
                : $charge->value;

            if ($charge->type === 'tax') {
                $totalTax += $amount;
            }

            if ($charge->type === 'shipping') {
                $totalShipping += $amount;
            }

            if ($charge->type === 'fee') {
                $totalFee += $amount;
            }

            $applied[] = [
                'charge_id' => $charge->id,
                'type' => $charge->type,
                'label' => $charge->label,
                'amount' => $amount,
                'meta' => ['mode' => $charge->mode],
            ];
        }

        $total = $afterDiscount + $totalTax + $totalShipping + $totalFee;

        return [
            'tax' => $totalTax,
            'shipping' => $totalShipping,
            'fee' => $totalFee,
            'total' => $total,
            'applied' => $applied,
        ];
    }
}
