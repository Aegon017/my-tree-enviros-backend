<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class PhonePeTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * To Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => [
                'required',
                'integer',
                'exists:orders,id',
                function ($attribute, $value, $fail) {
                    $order = \App\Models\Order::find($value);
                    if ($order && $order->user_id !== auth()->id()) {
                        $fail('The order must belong to the authenticated user.');
                    }
                },
            ],
            'amount' => 'required|integer|min:1|max:999999999',
            'merchant_transaction_id' => [
                'required',
                'string',
                'min:10',
                'max:255',
                'regex:/^[A-Za-z0-9_\-]+$/',
                'unique:order_payments,transaction_id',
            ],
            'user_id' => 'required|string|max:255',
            'user_mobile' => 'required|string|regex:/^[0-9]{10}$/',
        ];
    }

    /**
     * To Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required',
            'order_id.integer' => 'Order ID must be an integer',
            'order_id.exists' => 'The specified order does not exist',
            'amount.required' => 'Amount is required',
            'amount.integer' => 'Amount must be in paise (integers)',
            'amount.min' => 'Amount must be at least 1 paise',
            'merchant_transaction_id.required' => 'Merchant transaction ID is required',
            'merchant_transaction_id.unique' => 'This transaction ID has already been used',
            'merchant_transaction_id.regex' => 'Transaction ID can only contain letters, numbers, hyphens, and underscores',
            'user_mobile.regex' => 'Mobile number must be exactly 10 digits',
        ];
    }
}
