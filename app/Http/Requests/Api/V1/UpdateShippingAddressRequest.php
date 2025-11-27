<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateShippingAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'address' => ['sometimes', 'required', 'string', 'max:500'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'area' => ['sometimes', 'required', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'required', 'string', 'max:8'],
            'latitude' => ['sometimes', 'required', 'numeric'],
            'longitude' => ['sometimes', 'required', 'numeric'],
            'post_office_name' => ['nullable', 'string', 'max:255'],
            'post_office_branch_type' => ['nullable', 'string', 'max:100'],
            'is_default' => ['boolean'],
        ];
    }
}
