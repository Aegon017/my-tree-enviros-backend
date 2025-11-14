<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ShippingAddressResource;
use App\Models\ShippingAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class ShippingAddressController extends Controller
{
    use \App\Traits\ResponseHelpers;

    public function index(): JsonResponse
    {
        $addresses = ShippingAddress::where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success([
            'addresses' => ShippingAddressResource::collection($addresses),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'area' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:8'],

            // NEW FIELDS FOR SWIGGY FLOW
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'post_office_name' => ['nullable', 'string', 'max:255'],
            'post_office_branch_type' => ['nullable', 'string', 'max:100'],

            'is_default' => ['boolean'],
        ]);

        try {
            DB::beginTransaction();

            $address = new ShippingAddress($validated);
            $address->user_id = Auth::id();

            if ($validated['is_default'] ?? false) {
                ShippingAddress::where('user_id', Auth::id())
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $address->save();

            DB::commit();

            return $this->created([
                'address' => new ShippingAddressResource($address),
            ], 'Shipping address created successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Failed to create shipping address: ' . $e->getMessage());
        }
    }

    public function show(ShippingAddress $shippingAddress): JsonResponse
    {
        if ($shippingAddress->user_id !== Auth::id()) {
            return $this->unauthorized('You are not authorized to view this address');
        }

        return $this->success([
            'address' => new ShippingAddressResource($shippingAddress),
        ]);
    }

    public function update(Request $request, ShippingAddress $shippingAddress): JsonResponse
    {
        if ($shippingAddress->user_id !== Auth::id()) {
            return $this->unauthorized('You are not authorized to update this address');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'address' => ['sometimes', 'required', 'string', 'max:500'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'area' => ['sometimes', 'required', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'required', 'string', 'max:8'],

            // NEW FIELDS
            'latitude' => ['sometimes', 'required', 'numeric'],
            'longitude' => ['sometimes', 'required', 'numeric'],
            'post_office_name' => ['nullable', 'string', 'max:255'],
            'post_office_branch_type' => ['nullable', 'string', 'max:100'],

            'is_default' => ['boolean'],
        ]);

        try {
            DB::beginTransaction();

            if (($validated['is_default'] ?? false) && !$shippingAddress->is_default) {
                ShippingAddress::where('user_id', Auth::id())
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $shippingAddress->update($validated);

            DB::commit();

            return $this->success([
                'address' => new ShippingAddressResource($shippingAddress->fresh()),
            ], 'Shipping address updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Failed to update shipping address: ' . $e->getMessage());
        }
    }

    public function destroy(ShippingAddress $shippingAddress): JsonResponse
    {
        if ($shippingAddress->user_id !== Auth::id()) {
            return $this->unauthorized('You are not authorized to delete this address');
        }

        try {
            DB::beginTransaction();

            $shippingAddress->delete();

            DB::commit();

            return $this->noContent();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Failed to delete shipping address: ' . $e->getMessage());
        }
    }

    public function setDefault(ShippingAddress $shippingAddress): JsonResponse
    {
        if ($shippingAddress->user_id !== Auth::id()) {
            return $this->unauthorized('You are not authorized to update this address');
        }

        try {
            DB::beginTransaction();

            ShippingAddress::where('user_id', Auth::id())
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $shippingAddress->update(['is_default' => true]);

            DB::commit();

            return $this->success([
                'address' => new ShippingAddressResource($shippingAddress->fresh()),
            ], 'Default shipping address updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Failed to update default shipping address: ' . $e->getMessage());
        }
    }
}