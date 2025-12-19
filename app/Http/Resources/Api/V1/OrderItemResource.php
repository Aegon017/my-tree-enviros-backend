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
            'product_name' => $this->when($this->type === 'product', fn() => $this->productVariant->inventory->product->name ?? null),
            'tree_name' => $this->when(in_array($this->type, ['sponsor', 'adopt']), fn() => $this->tree->name ?? null),
            'product_variant' => $this->whenLoaded('productVariant', fn() => [
                'id' => $this->productVariant->id,
                'name' => $this->productVariant->name,
                'image_url' => $this->productVariant->getFirstMedia('images')->getFullUrl(),
                'product' => [
                    'name' => $this->productVariant->inventory->product->name ?? null,
                ],
            ]),
            'tree' => $this->whenLoaded('tree', fn() => [
                'id' => $this->tree->id,
                'name' => $this->tree->name,
                'image_url' => $this->tree->getFirstMedia('images')->getFullUrl(),
            ]),
            'tree_instance' => $this->whenLoaded('treeInstance', fn() => [
                'id' => $this->treeInstance->id,
                'image_url' => $this->treeInstance->getFirstMedia('images')->getFullUrl(),
            ]),
            'plan_details' => $this->whenLoaded('planPrice', fn() => [
                'name' => $this->planPrice->name,
                'billing_period' => $this->planPrice->billing_period,
            ]),
            'site_details' => $this->whenLoaded('initiativeSite', fn() => [
                'id' => $this->initiativeSite->id,
                'name' => $this->initiativeSite->name ?? $this->initiativeSite->location?->name,
                'location' => $this->initiativeSite->location?->name,
                'city' => $this->initiativeSite->city ?? $this->initiativeSite->location?->name,
                'country' => $this->initiativeSite->country ?? 'India',
            ]),
            'dedication' => $this->whenLoaded('dedication', fn() => [
                'name' => $this->dedication->name,
                'message' => $this->dedication->message,
                'occasion' => $this->dedication->occasion,
            ]),
            'name' => match ($this->type) {
                'product' => $this->productVariant->inventory->product->name ?? 'Product',
                'sponsor' => $this->planPrice->name ?? 'Sponsorship',
                'adopt' => $this->tree->name ?? 'Tree Adoption',
                'campaign' => 'Campaign Contribution',
                default => ucfirst($this->type),
            },
        ];
    }
}
