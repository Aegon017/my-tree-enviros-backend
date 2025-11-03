<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TreePlanPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $numericPrice = is_numeric($this->price) ? (float) $this->price : null;

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'numeric_price' => $numericPrice,
            'price' => $numericPrice !== null
                    ? number_format($numericPrice, 2)
                    : $this->price,
            'formatted_price' => $numericPrice !== null
                    ? '₹'.number_format($numericPrice, 2)
                    : $this->price,
            'is_active' => $this->is_active,
            'tree' => new TreeResource($this->whenLoaded('tree')),
            'plan' => new TreePricePlanResource($this->whenLoaded('plan')),
            'tree_id' => $this->tree_id,
            'plan_id' => $this->tree_price_plan_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
