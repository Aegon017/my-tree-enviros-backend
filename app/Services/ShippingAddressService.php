<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ShippingAddress;
use App\Repositories\ShippingAddressRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ShippingAddressService
{
    public function __construct(
        protected ShippingAddressRepository $repository
    ) {}

    public function getUserAddresses(int $userId): Collection
    {
        return $this->repository->getByUser($userId);
    }

    public function createAddress(int $userId, array $data): ShippingAddress
    {
        return DB::transaction(function () use ($userId, $data) {
            $data['user_id'] = $userId;

            if ($data['is_default'] ?? false) {
                $this->repository->unsetDefault($userId);
            }

            return $this->repository->create($data);
        });
    }

    public function updateAddress(ShippingAddress $address, array $data): ShippingAddress
    {
        return DB::transaction(function () use ($address, $data) {
            if (($data['is_default'] ?? false) && !$address->is_default) {
                $this->repository->unsetDefault($address->user_id);
            }

            $this->repository->update($address, $data);

            return $address->fresh();
        });
    }

    public function deleteAddress(ShippingAddress $address): void
    {
        DB::transaction(function () use ($address) {
            $this->repository->delete($address);
        });
    }

    public function setDefaultAddress(ShippingAddress $address): ShippingAddress
    {
        return DB::transaction(function () use ($address) {
            $this->repository->unsetDefault($address->user_id);
            $this->repository->update($address, ['is_default' => true]);

            return $address->fresh();
        });
    }
}
