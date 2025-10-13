<?php

namespace App\Http\Requests\Api\Auth;

use App\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SignUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/\s+/', '', (string) $this->phone),
        ]);
    }

    public function rules(): array
    {
        return [
            'country_code' => ['required', 'string', 'max:5'],
            'phone' => [
                'required',
                'string',
                new PhoneNumberRule($this->country_code),
                Rule::unique('users')->where(function ($query) {
                    return $query->where('country_code', $this->country_code);
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'country_code.required' => 'Country code is required.',
            'phone.required' => 'Phone number is required.',
            'phone.unique' => 'This phone number is already registered.',
        ];
    }
}
