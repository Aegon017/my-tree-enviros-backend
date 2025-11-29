<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'discount' => $this->total_discount,
            'tax' => $this->total_tax,
            'shipping' => $this->total_shipping,
            'fee' => $this->total_fee,
            'grand_total' => $this->grand_total,
            'currency' => $this->currency,
            'created_at' => $this->created_at->toDateTimeString(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'charges' => OrderChargeResource::collection($this->whenLoaded('orderCharges')),
            'payment' => $this->whenLoaded('payment', fn(): array => [
                'method' => $this->payment->payment_method,
                'status' => $this->payment->status,
                'transaction_id' => $this->payment->transaction_id,
            ]),
            'shipping_address' => $this->whenLoaded('shippingAddress', fn(): ?array => $this->shippingAddress ? [
                'id' => $this->shippingAddress->id,
                'name' => $this->shippingAddress->name,
                'phone' => $this->shippingAddress->phone,
                'address' => $this->shippingAddress->address,
                'area' => $this->shippingAddress->area,
                'city' => $this->shippingAddress->city,
                'postal_code' => $this->shippingAddress->postal_code,
            ] : null),
        ];
    }
}
