<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CartStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item_type' => ['required', Rule::in(['tree', 'product'])],
            'tree_instance_id' => 'nullable|exists:tree_instances,id',
            'tree_plan_price_id' => 'nullable|exists:tree_plan_prices,id',
            'product_id' => 'nullable|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1|max:100',
            'name' => 'nullable|string|max:100',
            'occasion' => 'nullable|string|max:100',
            'message' => 'nullable|string|max:500',
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
