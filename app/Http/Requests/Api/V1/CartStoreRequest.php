<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class CartStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:product,sponsor,adopt',
            'product_variant_id' => 'required_if:type,product|exists:product_variants,id',
            'quantity' => 'required_if:type,product|integer|min:1',
            'tree_id' => 'required_if:type,sponsor,adopt|exists:trees,id',
            'plan_id' => 'required_if:type,sponsor,adopt|exists:plans,id',
            'plan_price_id' => 'required_if:type,sponsor,adopt|exists:plan_prices,id',
            'dedication' => 'nullable|array',
            'dedication.name' => 'sometimes|string',
            'dedication.occasion' => 'sometimes|string',
            'dedication.message' => 'sometimes|string',
        ];
    }

    public function messages(): array
    {
        return [
            'item_type.required' => 'Item type is required.',
            'item_type.in' => 'Invalid item type.',
        ];
    }
}
