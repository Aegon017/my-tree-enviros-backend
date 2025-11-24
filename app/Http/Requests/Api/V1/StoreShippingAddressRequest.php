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
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'area' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:8'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'post_office_name' => ['nullable', 'string', 'max:255'],
            'post_office_branch_type' => ['nullable', 'string', 'max:100'],
            'is_default' => ['boolean'],
        ];
    }
}
