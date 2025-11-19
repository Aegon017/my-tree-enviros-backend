<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CartUpdateRequest extends FormRequest
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
            'quantity' => 'sometimes|integer|min:1|max:100',
            'plan_price_id' => 'sometimes|exists:plan_prices,id',
            'dedication' => 'sometimes|array',
            'dedication.name' => 'sometimes|string',
            'dedication.occasion' => 'sometimes|string',
            'dedication.message' => 'sometimes|string',
        ];
    }
}
