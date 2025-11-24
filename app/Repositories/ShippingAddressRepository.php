<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ShippingAddress;
use Illuminate\Database\Eloquent\Collection;

class ShippingAddressRepository
{
    public function getByUser(int $userId): Collection
    {
        return ShippingAddress::where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data): ShippingAddress
    {
        return ShippingAddress::create($data);
    }

    public function update(ShippingAddress $address, array $data): bool
    {
        return $address->update($data);
    }

    public function delete(ShippingAddress $address): ?bool
    {
        return $address->delete();
    }

    public function unsetDefault(int $userId): void
    {
        ShippingAddress::where('user_id', $userId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
