<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class PhonePeVerifyRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'merchant_transaction_id' => 'required|string|min:5|max:255',
            'phonepe_transaction_id' => 'nullable|string|max:255',
            'order_reference' => [
                'required',
                'string',
                'exists:orders,reference_number',
                function ($attribute, $value, $fail) {
                    $order = \App\Models\Order::where('reference_number', $value)->first();
                    if ($order && $order->user_id !== auth()->id()) {
                        $fail('The order must belong to the authenticated user.');
                    }
                },
            ],
            'amount' => 'required|integer|min:1|max:999999999',
            'status' => 'required|in:SUCCESS,FAILED,PENDING',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'merchant_transaction_id.required' => 'Merchant transaction ID is required',
            'order_reference.required' => 'Order reference number is required',
            'order_reference.exists' => 'The specified order reference does not exist',
            'amount.required' => 'Amount is required',
            'amount.integer' => 'Amount must be in paise (integers)',
            'status.required' => 'Payment status is required',
            'status.in' => 'Payment status must be one of: SUCCESS, FAILED, PENDING',
        ];
    }
}
