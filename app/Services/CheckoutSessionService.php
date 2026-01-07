<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CheckoutSession;
use App\Models\ShippingAddress;
use App\Services\Orders\OrderSnapshotService;
use Illuminate\Support\Str;

final class CheckoutSessionService
{
    public function __construct(
        private readonly OrderSnapshotService $snapshot
    ) {}

    /**
     * Create a new checkout session
     */
    public function createSession(array $data, int $userId): CheckoutSession
    {
        // Calculate pricing
        $pricing = $this->calculatePricing($data['items'], $data['coupon_code'] ?? null);

        // Snapshot shipping address
        $shippingSnapshot = null;
        if (isset($data['shipping_address_id'])) {
            $shippingAddress = ShippingAddress::find($data['shipping_address_id']);
            if ($shippingAddress) {
                $shippingSnapshot = $this->snapshot->createShippingAddressSnapshot($shippingAddress);
            }
        }

        return CheckoutSession::create([
            'user_id' => $userId,
            'session_token' => Str::random(64),
            'items' => $data['items'],
            'pricing' => $pricing,
            'shipping_address_id' => $data['shipping_address_id'] ?? null,
            'shipping_address_snapshot' => $shippingSnapshot,
            'payment_method' => $data['payment_method'] ?? 'razorpay',
            'currency' => $data['currency'] ?? 'INR',
            'coupon_code' => $data['coupon_code'] ?? null,
            'coupon_id' => $data['coupon_id'] ?? null,
            'coupon_discount' => $pricing['discount'] ?? 0,
            'expires_at' => now()->addMinutes(30),
            'status' => 'active',
        ]);
    }

    /**
     * Find session by token
     */
    public function findByToken(string $token): ?CheckoutSession
    {
        return CheckoutSession::where('session_token', $token)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Find session by gateway order ID
     */
    public function findByGatewayOrderId(string $gatewayOrderId): ?CheckoutSession
    {
        return CheckoutSession::where('gateway_order_id', $gatewayOrderId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Calculate pricing for items
     */
    private function calculatePricing(array $items, ?string $couponCode): array
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $quantity = $item['quantity'] ?? 1;

            if ($item['type'] === 'campaign') {
                $subtotal += (float) ($item['amount'] ?? 0);
            } else {
                $price = (float) ($item['price'] ?? $item['amount'] ?? 0);
                $subtotal += $price * $quantity;
            }
        }

        // TODO: Integrate with CouponService for discount calculation
        $discount = 0;

        // TODO: Calculate tax based on items
        $tax = 0;

        // TODO: Calculate shipping based on items
        $shipping = 0;

        $grandTotal = $subtotal - $discount + $tax + $shipping;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'grand_total' => $grandTotal,
        ];
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions(): int
    {
        return CheckoutSession::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }
}
