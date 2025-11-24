<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreShippingAddressRequest;
use App\Http\Requests\Api\V1\UpdateShippingAddressRequest;
use App\Http\Resources\Api\V1\ShippingAddressResource;
use App\Models\ShippingAddress;
use App\Services\ShippingAddressService;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShippingAddressController extends Controller
{
    use ResponseHelpers;

    public function __construct(
        protected ShippingAddressService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $addresses = $this->service->getUserAddresses($request->user()->id);

        return $this->success([
            'addresses' => ShippingAddressResource::collection($addresses),
        ]);
    }

    public function store(StoreShippingAddressRequest $request): JsonResponse
    {
        try {
            $address = $this->service->createAddress($request->user()->id, $request->validated());

            return $this->created([
                'address' => new ShippingAddressResource($address),
            ], 'Shipping address created successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to create shipping address: ' . $e->getMessage());
        }
    }

    public function show(ShippingAddress $shippingAddress): JsonResponse
    {
        return $this->success([
            'address' => new ShippingAddressResource($shippingAddress),
        ]);
    }

    public function update(UpdateShippingAddressRequest $request, ShippingAddress $shippingAddress): JsonResponse
    {
        try {
            $address = $this->service->updateAddress($shippingAddress, $request->validated());

            return $this->success([
                'address' => new ShippingAddressResource($address),
            ], 'Shipping address updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update shipping address: ' . $e->getMessage());
        }
    }

    public function destroy(ShippingAddress $shippingAddress): JsonResponse
    {
        try {
            $this->service->deleteAddress($shippingAddress);

            return $this->noContent();
        } catch (\Exception $e) {
            return $this->error('Failed to delete shipping address: ' . $e->getMessage());
        }
    }

    public function setDefault(ShippingAddress $shippingAddress): JsonResponse
    {
        try {
            $address = $this->service->setDefaultAddress($shippingAddress);

            return $this->success([
                'address' => new ShippingAddressResource($address),
            ], 'Default shipping address updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update default shipping address: ' . $e->getMessage());
        }
    }
}