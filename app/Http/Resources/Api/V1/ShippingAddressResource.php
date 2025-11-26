<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shipping Address API Resource
 *
 * @OA\Schema(
 *     title="Shipping Address",
 *     description="Shipping address resource representation",
 *     type="object",
 *     required={"id", "name", "phone", "address", "city", "area", "postal_code", "is_default", "created_at", "updated_at"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="address", type="string", example="123 Main Street"),
 *     @OA\Property(property="city", type="string", example="New York"),
 *     @OA\Property(property="area", type="string", example="Downtown"),
 *     @OA\Property(property="postal_code", type="string", example="10001"),
 *     @OA\Property(property="is_default", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z")
 * )
 */
final class ShippingAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'area' => $this->area,
            'postal_code' => $this->postal_code,
            'is_default' => $this->is_default,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
