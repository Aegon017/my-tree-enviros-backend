<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $itemDetails = $this->getItemDetails();

        return [
            'id' => $this->id,
            'cart_id' => $this->cart_id,
            'item_type' => $itemDetails['type'],
            'quantity' => $this->quantity,
            'price' => (float) $this->price,
            'formatted_price' => '₹' . number_format((float) $this->price, 2),
            'subtotal' => $this->subtotal(),
            'formatted_subtotal' => '₹' . number_format($this->subtotal(), 2),
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
            'type' => $details['type'],
            'name' => $details['name'],
            'sku' => $details['sku'] ?? null,
            'image' => $details['image'] ?? null,
        ];

        // Add type-specific details
        switch ($details['type']) {
            case 'tree':
                return array_merge($baseDetails, [
                    'plan' => [
                        'name' => $details['plan_name'] ?? null,
                        'type' => $details['plan_type'] ?? null,
                        'duration' => $details['duration'] ?? null,
                    ],
                    'location' => $details['location'] ?? null,
                ]);

            case 'product':
                // Load the full product data from the cartable relationship
                $productData = null;
                if ($this->cartable) {
                    $productResource = new ProductResource($this->cartable);
                    $productData = $productResource->toArray(request());
                }

                return array_merge($baseDetails, [
                    'variant' => $details['variant'] ?? null,
                    'color' => $details['color'] ?? null,
                    'size' => $details['size'] ?? null,
                    'product' => $productData,
                ]);

            case 'campaign':
                return array_merge($baseDetails, [
                    'campaign_type' => $details['campaign_type'] ?? null,
                    'location' => $details['location'] ?? null,
                    'description' => $details['description'] ?? null,
                ]);

            default:
                return $baseDetails;
        }
    }
}
