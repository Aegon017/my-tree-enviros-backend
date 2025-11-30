<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            'items.*.campaign_id' => ['nullable', 'integer'],
            'items.*.tree_id' => ['nullable', 'integer'],
            'items.*.plan_id' => ['nullable', 'integer'],
            'items.*.plan_price_id' => ['nullable', 'integer'],
            'items.*.tree_instance_id' => ['nullable', 'integer'],
            'items.*.sponsor_quantity' => ['nullable', 'integer', 'min:1'],
            'coupon_code' => ['nullable', 'string'],
            'shipping_address_id' => ['nullable', 'integer', 'exists:shipping_addresses,id'],
        ];
    }
}
