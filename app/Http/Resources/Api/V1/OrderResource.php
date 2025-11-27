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
            'order_number' => $this->reference_number,
            'user_id' => $this->user_id,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'gst_amount' => $this->gst_amount,
            'cgst_amount' => $this->cgst_amount,
            'sgst_amount' => $this->sgst_amount,
            'formatted_subtotal' => '₹'.number_format((float) $this->subtotal, 2),
            'formatted_discount' => '₹'.number_format((float) $this->discount, 2),
            'formatted_total' => '₹'.number_format((float) $this->total, 2),
            'formatted_gst' => '₹'.number_format((float) $this->gst_amount, 2),
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'paid_at' => $this->paid_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'shipping_address' => $this->whenLoaded('shippingAddress', fn (): array => [
                'id' => $this->shippingAddress->id,
                'name' => $this->shippingAddress->name,
                'phone' => $this->shippingAddress->phone,
                'address_line_1' => $this->shippingAddress->address_line_1,
                'address_line_2' => $this->shippingAddress->address_line_2,
                'city' => $this->shippingAddress->city,
                'state' => $this->shippingAddress->state,
                'postal_code' => $this->shippingAddress->postal_code,
                'country' => $this->shippingAddress->country,
            ]),
            'coupon' => $this->whenLoaded('coupon', fn (): ?array => $this->coupon ? [
                'id' => $this->coupon->id,
                'code' => $this->coupon->code,
                'type' => $this->coupon->type,
                'value' => $this->coupon->value,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
