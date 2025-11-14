<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:128',
            'phone' => 'required|string|max:32',
            'address' => 'required|string|max:512',
            'area' => 'nullable|string|max:128',
            'city' => 'nullable|string|max:128',
            'postal_code' => 'nullable|string|max:12',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'post_office_name' => 'nullable|string|max:255',
            'post_office_branch_type' => 'nullable|string|max:64',
            'is_default' => 'nullable|boolean',
        ];
    }
}
