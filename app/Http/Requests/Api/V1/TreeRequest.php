<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class TreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_lat' => 'required|numeric',
            'user_lng' => 'required|numeric',
            'radius_km' => 'sometimes|numeric|min:1',
            'per_page' => 'sometimes|integer|min:1|max:50',
            'type' => 'sometimes|in:sponsorship,adoption,all',
        ];
    }
}
