<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Charge;

final class OrderPricingService
{
    public function calculateTotals(array $items, ?array $couponResult = null): array
    {
        $subtotal = collect($items)->sum(fn ($item): int|float => $item['quantity'] * $item['amount']);

        $discount = $couponResult['discount'] ?? 0;
        $afterDiscount = max($subtotal - $discount, 0);

        $charges = Charge::where('is_active', true)->get();

        $totalTax = 0;
        $totalShipping = 0;
        $totalFee = 0;
        $appliedCharges = [];

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

            $appliedCharges[] = [
                'charge_id' => $charge->id,
                'type' => $charge->type,
                'label' => $charge->label,
                'amount' => $amount,
                'meta' => ['mode' => $charge->mode],
            ];
        }

        $grandTotal = $afterDiscount + $totalTax + $totalShipping + $totalFee;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'after_discount' => $afterDiscount,
            'tax' => $totalTax,
            'shipping' => $totalShipping,
            'fee' => $totalFee,
            'grand_total' => $grandTotal,
            'applied_charges' => $appliedCharges,
        ];
    }
}
