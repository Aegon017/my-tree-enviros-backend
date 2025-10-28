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
            'order_number' => $this->order_number,
            'user_id' => $this->user_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount,
            'gst_amount' => $this->gst_amount,
            'cgst_amount' => $this->cgst_amount,
            'sgst_amount' => $this->sgst_amount,
            'formatted_total' => '₹' . number_format((float) $this->total_amount, 2),
            'formatted_discount' => '₹' . number_format((float) $this->discount_amount, 2),
            'formatted_gst' => '₹' . number_format((float) $this->gst_amount, 2),
            'currency' => $this->currency,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'shipping_address' => $this->whenLoaded('shippingAddress', function () {
                return [
                    'id' => $this->shippingAddress->id,
                    'name' => $this->shippingAddress->name,
                    'phone' => $this->shippingAddress->phone,
                    'address_line_1' => $this->shippingAddress->address_line_1,
                    'address_line_2' => $this->shippingAddress->address_line_2,
                    'city' => $this->shippingAddress->city,
                    'state' => $this->shippingAddress->state,
                    'postal_code' => $this->shippingAddress->postal_code,
                    'country' => $this->shippingAddress->country,
                ];
            }),
            'coupon' => $this->whenLoaded('coupon', function () {
                return $this->coupon ? [
                    'id' => $this->coupon->id,
                    'code' => $this->coupon->code,
                    'type' => $this->coupon->type,
                    'value' => $this->coupon->value,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
