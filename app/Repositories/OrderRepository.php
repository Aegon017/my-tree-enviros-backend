<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderCharge;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\DB;

final class OrderRepository
{
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function createItem(array $data): OrderItem
    {
        return OrderItem::create($data);
    }

    public function createCharge(array $data): OrderCharge
    {
        return OrderCharge::create($data);
    }

    public function createPayment(array $data): OrderPayment
    {
        return OrderPayment::create($data);
    }

    public function findById(int $id): ?Order
    {
        return Order::find($id);
    }

    public function update(Order $order, array $data): bool
    {
        return $order->update($data);
    }

    public function attachCoupon(Order $order, int $couponId): void
    {
        DB::table(config('couponables.pivot_table', 'couponables'))->insert([
            'coupon_id' => $couponId,
            'couponable_type' => Order::class,
            'couponable_id' => $order->id,
            'redeemed_at' => now(),
        ]);
    }
}
