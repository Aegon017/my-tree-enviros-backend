<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Auth;

use App\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ResendOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_code' => ['required', 'string', 'max:5'],
            'phone' => [
                'required',
                'string',
                new PhoneNumberRule($this->country_code),
                Rule::exists('users')->where(
                    fn ($query) => $query->where('country_code', $this->country_code)
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'country_code.required' => 'Please select a country code.',
            'phone.required' => 'The phone number field is required.',
            'phone.exists' => 'No user found with this phone number and country code.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/\s+/', '', (string) $this->phone),
        ]);
    }
}
