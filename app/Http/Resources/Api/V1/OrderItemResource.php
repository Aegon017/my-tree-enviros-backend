<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $itemDetails = $this->getItemDetails();

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'item_type' => $itemDetails['item_type'],
            'quantity' => $this->quantity,
            'price' => $this->price,
            'formatted_price' => '₹'.number_format((float) $this->price, 2),
            'discount_amount' => $this->discount_amount,
            'gst_amount' => $this->gst_amount,
            'cgst_amount' => $this->cgst_amount,
            'sgst_amount' => $this->sgst_amount,
            'formatted_discount' => '₹'.number_format((float) $this->discount_amount, 2),
            'formatted_gst' => '₹'.number_format((float) $this->gst_amount, 2),
            'subtotal' => $this->subtotal(),
            'formatted_subtotal' => '₹'.number_format((float) $this->subtotal(), 2),
            'total' => $this->total(),
            'formatted_total' => '₹'.number_format((float) $this->total(), 2),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_renewal' => $this->is_renewal,
            'item' => $this->formatItemDetails($itemDetails),
            'options' => $this->options,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Format item details based on type
     */
    private function formatItemDetails(array $details): array
    {
        $baseDetails = [
            'type' => $details['item_type'],
            'name' => $details['name'],
            'sku' => $details['sku'] ?? null,
            'image' => $details['image'] ?? null,
        ];

        // Add type-specific details
        return match ($details['item_type']) {
            'tree' => array_merge($baseDetails, [
                'plan' => [
                    'name' => $details['plan_name'] ?? null,
                    'type' => $details['plan_type'] ?? null,
                    'duration' => $details['duration'] ?? null,
                ],
                'location' => $details['location'] ?? null,
            ]),
            'product' => array_merge($baseDetails, [
                'variant' => $details['variant'] ?? null,
                'color' => $details['color'] ?? null,
                'size' => $details['size'] ?? null,
            ]),
            'campaign' => array_merge($baseDetails, [
                'campaign_type' => $details['campaign_type'] ?? null,
                'location' => $details['location'] ?? null,
                'description' => $details['description'] ?? null,
            ]),
            default => $baseDetails,
        };
    }
}
