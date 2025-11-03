<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Auth;

use App\Enums\UserTypeEnum;
use App\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SignUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(UserTypeEnum::class)],
            'country_code' => ['required', 'string', 'max:5'],
            'phone' => [
                'required',
                'string',
                new PhoneNumberRule($this->country_code),
                Rule::unique('users')->where(fn ($query) => $query->where('country_code', $this->country_code)),
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/\s+/', '', (string) $this->phone),
        ]);
    }
}
