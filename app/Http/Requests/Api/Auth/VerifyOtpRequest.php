<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Auth;

use App\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;

final class VerifyOtpRequest extends FormRequest
{
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
            ],
            'otp' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'The OTP code is required.',
            'otp.digits' => 'The OTP code must be exactly 6 digits.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/\s+/', '', (string) $this->phone),
        ]);
    }
}