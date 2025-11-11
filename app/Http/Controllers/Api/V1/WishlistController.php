<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use App\Traits\ResponseHelpers;

class WishlistController extends Controller
{
    use ResponseHelpers;

    public function __construct(
        private WishlistService $service
    ) {}

    public function index(Request $request)
    {
        $wishlist = $this->service->get($request->user());
        return $this->success(['wishlist' => $wishlist]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $result = $this->service->add($request->user(), $data);

        if (isset($result['error'])) return $this->error($result['message'], 422);

        return $this->success(['wishlist' => $result], 'Item added');
    }

    public function destroy(Request $request, string $id)
    {
        $wishlist = $this->service->remove($request->user(), (int)$id);

        if (! $wishlist) return $this->notFound('Not found');

        return $this->success(['wishlist' => $wishlist], 'Removed');
    }

    public function clear(Request $request)
    {
        $wishlist = $this->service->clear($request->user());

        return $this->success(['wishlist' => $wishlist], 'Cleared');
    }

    public function check(Request $request, string $productId)
    {
        $variantId = $request->query('variant_id');

        $result = $this->service->check($request->user(), (int)$productId, $variantId ? (int)$variantId : null);

        return $this->success([
            'in_wishlist' => $result,
            'product_id' => (int) $productId,
            'variant_id' => $variantId ?? null,
        ]);
    }
}
