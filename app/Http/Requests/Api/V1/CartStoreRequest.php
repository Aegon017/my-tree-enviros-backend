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
            'item_type' => ['required', Rule::in(['tree', 'product'])],
            'tree_instance_id' => ['nullable', 'integer', 'exists:tree_instances,id', Rule::requiredIf($this->input('item_type') === 'tree')],
            'tree_plan_price_id' => ['nullable', 'integer', 'exists:tree_plan_prices,id', Rule::requiredIf($this->input('item_type') === 'tree')],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'name' => ['nullable', 'string', 'max:100'],
            'occasion' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:500'],
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