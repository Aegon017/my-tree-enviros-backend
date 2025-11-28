<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
            'total_amount' => $this->total_amount,
            'product_name' => $this->when($this->type === 'product', fn () => $this->productVariant->product->name ?? null),
            'tree_name' => $this->when(in_array($this->type, ['sponsor', 'adopt']), fn () => $this->tree->name ?? null),
        ];
    }
}
