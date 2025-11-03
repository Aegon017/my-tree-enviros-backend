<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WishlistResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Wishlist",
 *     description="Wishlist management for e-commerce products"
 * )
 */
final class WishlistController extends Controller
{
    use ResponseHelpers;

    /**
     * @OA\Get(
     *     path="/api/v1/wishlist",
     *     summary="Get user's wishlist",
     *     description="Retrieve the authenticated user's wishlist with all items",
     *     tags={"Wishlist"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="wishlist", ref="#/components/schemas/Wishlist")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $wishlist = $this->getOrCreateWishlist($request);

        $wishlist->load([
            'items.product.productCategory',
            'items.product.inventory',
            'items.productVariant.inventory.product',
        ]);

        return $this->success([
            'wishlist' => new WishlistResource($wishlist),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/wishlist/items",
     *     summary="Add item to wishlist",
     *     description="Add a product or product variant to the user's wishlist",
     *     tags={"Wishlist"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"product_id"},
     *
     *             @OA\Property(
     *                 property="product_id",
     *                 type="integer",
     *                 description="Product ID",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="product_variant_id",
     *                 type="integer",
     *                 description="Product Variant ID (optional)",
     *                 example=1
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Item added to wishlist successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Item added to wishlist successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="wishlist", ref="#/components/schemas/Wishlist")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or item already in wishlist",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="This item is already in your wishlist")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        return DB::transaction(function () use ($request, $validated): JsonResponse {
            $wishlist = $this->getOrCreateWishlist($request);

            // Verify product exists and is active
            $product = Product::where('id', $validated['product_id'])
                ->where('is_active', true)
                ->first();

            if (! $product) {
                return $this->notFound('Product not found or not available');
            }

            // If variant is specified, verify it belongs to the product
            if (! empty($validated['product_variant_id'])) {
                $variant = ProductVariant::where('id', $validated['product_variant_id'])
                    ->whereHas('inventory', function ($q) use ($validated): void {
                        $q->where('product_id', $validated['product_id']);
                    })
                    ->first();

                if (! $variant) {
                    return $this->error('Invalid product variant', 422);
                }
            }

            // Check if item already exists in wishlist
            $existingItem = WishlistItem::where('wishlist_id', $wishlist->id)
                ->where('product_id', $validated['product_id'])
                ->where('product_variant_id', $validated['product_variant_id'] ?? null)
                ->first();

            if ($existingItem) {
                return $this->error('This item is already in your wishlist', 422);
            }

            // Create wishlist item
            WishlistItem::create([
                'wishlist_id' => $wishlist->id,
                'product_id' => $validated['product_id'],
                'product_variant_id' => $validated['product_variant_id'] ?? null,
            ]);

            // Reload wishlist with relationships
            $wishlist->load([
                'items.product.productCategory',
                'items.product.inventory',
                'items.productVariant.inventory.product',
            ]);

            return $this->success([
                'wishlist' => new WishlistResource($wishlist),
            ], 'Item added to wishlist successfully');
        });
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/wishlist/items/{id}",
     *     summary="Remove item from wishlist",
     *     description="Remove a specific item from the user's wishlist",
     *     tags={"Wishlist"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Wishlist Item ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Item removed from wishlist",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Item removed from wishlist"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="wishlist", ref="#/components/schemas/Wishlist")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Wishlist item not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Wishlist item not found")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        return DB::transaction(function () use ($request, $id): JsonResponse {
            $wishlist = $this->getOrCreateWishlist($request);

            $wishlistItem = WishlistItem::where('wishlist_id', $wishlist->id)
                ->where('id', $id)
                ->first();

            if (! $wishlistItem) {
                return $this->notFound('Wishlist item not found');
            }

            $wishlistItem->delete();

            // Reload wishlist with relationships
            $wishlist->load([
                'items.product.productCategory',
                'items.product.inventory',
                'items.productVariant.inventory.product',
            ]);

            return $this->success([
                'wishlist' => new WishlistResource($wishlist),
            ], 'Item removed from wishlist');
        });
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/wishlist",
     *     summary="Clear wishlist",
     *     description="Remove all items from the user's wishlist",
     *     tags={"Wishlist"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Wishlist cleared successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Wishlist cleared successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="wishlist", ref="#/components/schemas/Wishlist")
     *             )
     *         )
     *     )
     * )
     */
    public function clear(Request $request): JsonResponse
    {
        return DB::transaction(function () use ($request): JsonResponse {
            $wishlist = $this->getOrCreateWishlist($request);

            $wishlist->clear();

            return $this->success([
                'wishlist' => new WishlistResource($wishlist->fresh('items')),
            ], 'Wishlist cleared successfully');
        });
    }

    /**
     * @OA\Post(
     *     path="/api/v1/wishlist/items/{id}/move-to-cart",
     *     summary="Move wishlist item to cart",
     *     description="Move a product from wishlist to shopping cart",
     *     tags={"Wishlist"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Wishlist Item ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="quantity",
     *                 type="integer",
     *                 description="Quantity to add to cart",
     *                 example=1,
     *                 default=1
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Item moved to cart successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Item moved to cart successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="cart", ref="#/components/schemas/Cart"),
     *                 @OA\Property(property="wishlist", ref="#/components/schemas/Wishlist")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Wishlist item not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Product is out of stock"
     *     )
     * )
     */
    public function moveToCart(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'nullable|integer|min:1|max:100',
        ]);

        return DB::transaction(function () use ($request, $id, $validated): JsonResponse {
            $wishlist = $this->getOrCreateWishlist($request);

            $wishlistItem = WishlistItem::with(['product.inventory', 'productVariant.inventory'])
                ->where('wishlist_id', $wishlist->id)
                ->where('id', $id)
                ->first();

            if (! $wishlistItem) {
                return $this->notFound('Wishlist item not found');
            }

            // Check if product is in stock
            if (! $wishlistItem->isInStock()) {
                return $this->error('Product is out of stock', 422);
            }

            $quantity = $validated['quantity'] ?? 1;

            // Check if sufficient stock is available
            if ($wishlistItem->getStockQuantity() < $quantity) {
                return $this->error(
                    'Insufficient stock. Only '.$wishlistItem->getStockQuantity().' available',
                    422
                );
            }

            // Get or create cart
            $cart = \App\Models\Cart::firstOrCreate(
                ['user_id' => $request->user()->id],
                ['expires_at' => now()->addDays(7)]
            );

            // Add to cart
            if ($wishlistItem->isVariant()) {
                $cartableType = ProductVariant::class;
                $cartableId = $wishlistItem->product_variant_id;
                $price = $wishlistItem->productVariant->price ?? 0;
                $options = [
                    'variant' => [
                        'sku' => $wishlistItem->productVariant->sku,
                        'color' => $wishlistItem->productVariant->color,
                        'size' => $wishlistItem->productVariant->size,
                    ],
                    'product_name' => $wishlistItem->productVariant->inventory->product->name ?? 'Product',
                ];
            } else {
                $cartableType = Product::class;
                $cartableId = $wishlistItem->product_id;
                $price = $wishlistItem->product->price ?? 0;
                $options = [
                    'product_name' => $wishlistItem->product->name,
                ];
            }

            // Check if item already exists in cart
            $existingCartItem = \App\Models\CartItem::where('cart_id', $cart->id)
                ->where('cartable_type', $cartableType)
                ->where('cartable_id', $cartableId)
                ->first();

            if ($existingCartItem) {
                $existingCartItem->quantity += $quantity;
                $existingCartItem->save();
            } else {
                \App\Models\CartItem::create([
                    'cart_id' => $cart->id,
                    'cartable_type' => $cartableType,
                    'cartable_id' => $cartableId,
                    'quantity' => $quantity,
                    'price' => $price,
                    'options' => $options,
                ]);
            }

            // Remove from wishlist
            $wishlistItem->delete();

            // Reload relationships
            $cart->load(['items.cartable']);
            $wishlist->load([
                'items.product.productCategory',
                'items.product.inventory',
                'items.productVariant.inventory.product',
            ]);

            return $this->success([
                'cart' => new \App\Http\Resources\Api\V1\CartResource($cart),
                'wishlist' => new WishlistResource($wishlist),
            ], 'Item moved to cart successfully');
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v1/wishlist/check/{productId}",
     *     summary="Check if product is in wishlist",
     *     description="Check if a specific product or variant is in the user's wishlist",
     *     tags={"Wishlist"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="variant_id",
     *         in="query",
     *         description="Product Variant ID (optional)",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Check result",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="in_wishlist", type="boolean", example=true),
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="variant_id", type="integer", example=1, nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function check(Request $request, string $productId): JsonResponse
    {
        $wishlist = $this->getOrCreateWishlist($request);
        $variantId = $request->query('variant_id');

        $inWishlist = $wishlist->hasProduct((int) $productId, $variantId ? (int) $variantId : null);

        return $this->success([
            'in_wishlist' => $inWishlist,
            'product_id' => (int) $productId,
            'variant_id' => $variantId ? (int) $variantId : null,
        ]);
    }

    /**
     * Get or create user's wishlist
     */
    private function getOrCreateWishlist(Request $request): Wishlist
    {
        $user = $request->user();

        $wishlist = Wishlist::where('user_id', $user->id)->first();

        if (! $wishlist) {
            return Wishlist::create([
                'user_id' => $user->id,
            ]);
        }

        return $wishlist;
    }
}
