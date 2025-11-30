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
            $base = $charge->type === \App\Enums\ChargeTypeEnum::TAX ? $afterDiscount : $subtotal;

            $amount = $charge->mode === \App\Enums\ChargeModeEnum::PERCENTAGE
                ? $base * ($charge->value / 100)
                : $charge->value;

            if ($charge->type === \App\Enums\ChargeTypeEnum::TAX) {
                $totalTax += $amount;
            }

            if ($charge->type === \App\Enums\ChargeTypeEnum::SHIPPING) {
                $totalShipping += $amount;
            }

            if ($charge->type === \App\Enums\ChargeTypeEnum::FEE) {
                $totalFee += $amount;
            }

            $appliedCharges[] = [
                'charge_id' => $charge->id,
                'type' => $charge->type->value,
                'label' => $charge->label,
                'amount' => $amount,
                'meta' => ['mode' => $charge->mode->value],
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
