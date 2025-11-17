<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CartStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'              => 'required|string|in:product,sponsor,adopt',
            'product_variant_id' => 'required_if:type,product|exists:product_variants,id',
            'quantity'          => 'required_if:type,product|integer|min:1',
            'tree_id'           => 'required_if:type,sponsor|exists:trees,id',
            'plan_id'           => 'required_if:type,sponsor|exists:plans,id',
            'plan_price_id'     => 'required_if:type,sponsor|exists:plan_prices,id',
            'tree_instance_id'  => 'required_if:type,adopt|exists:tree_instances,id',
            'adopt_plan_id'     => 'required_if:type,adopt|exists:plans,id',
            'dedication'    => 'nullable|array',
            'dedication.name'     => 'sometimes|string',
            'dedication.occasion' => 'sometimes|string',
            'dedication.message'  => 'sometimes|string',
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
