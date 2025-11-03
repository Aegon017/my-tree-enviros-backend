<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CartResource;
use App\Models\Campaign;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TreeInstance;
use App\Models\TreePlanPrice;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Cart",
 *     description="Shopping cart management for all item types (trees, products, campaigns)"
 * )
 */
final class CartController extends Controller
{
    use ResponseHelpers;

    /**
     * @OA\Get(
     *     path="/api/v1/cart",
     *     summary="Get user's cart",
     *     description="Retrieve the authenticated user's shopping cart with all items",
     *     tags={"Cart"},
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
     *                 @OA\Property(property="cart", ref="#/components/schemas/Cart")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);

        $cart->load([
            'items.cartable' => function ($query): void {
                // For products, load the full product data with inventory and variants
                if ($query->getModel() instanceof Product) {
                    $query->with([
                        'inventory.productVariants.variant.color',
                        'inventory.productVariants.variant.size',
                        'inventory.productVariants.variant.planter',
                        'productCategory',
                    ]);
                }
                // For product variants, load the product inventory data
                elseif ($query->getModel() instanceof ProductVariant) {
                    $query->with('inventory.product');
                }
            },
        ]);

        // Clean up expired items if cart expired
        if ($cart->isExpired()) {
            $cart->clearExpiredItems();
        }

        return $this->success([
            'cart' => new CartResource($cart),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cart/items",
     *     summary="Add item to cart",
     *     description="Add a tree, product, or campaign to the shopping cart",
     *     tags={"Cart"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"item_type"},
     *
     *             @OA\Property(property="item_type", type="string", enum={"tree", "product", "campaign"}, example="product", description="Type of item to add"),
     *             @OA\Property(property="tree_instance_id", type="integer", example=1, description="Required if item_type is 'tree'"),
     *             @OA\Property(property="tree_plan_price_id", type="integer", example=1, description="Required if item_type is 'tree'"),
     *             @OA\Property(property="product_id", type="integer", example=1, description="Required if item_type is 'product'"),
     *             @OA\Property(property="product_variant_id", type="integer", example=1, description="Optional for products"),
     *             @OA\Property(property="campaign_id", type="integer", example=1, description="Required if item_type is 'campaign'"),
     *             @OA\Property(property="quantity", type="integer", example=2, minimum=1, maximum=100, description="Quantity (default: 1, trees always 1)"),
     *             @OA\Property(property="amount", type="number", format="float", example=1000.00, description="Custom amount for campaigns")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Item added to cart successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Item added to cart successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="cart", ref="#/components/schemas/Cart")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or item not available",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_type' => [
                'required',
                'string',
                Rule::in(['tree', 'product', 'campaign']),
            ],
            // Allow either explicit instance OR (tree_id + location_id) to be provided
            'tree_instance_id' => 'nullable|exists:tree_instances,id',
            'tree_id' => 'required_if:item_type,tree|nullable|exists:trees,id',
            'location_id' => 'required_if:item_type,tree|nullable|exists:locations,id',
            'tree_plan_price_id' => 'required_if:item_type,tree|nullable|exists:tree_plan_prices,id',
            'product_id' => 'required_if:item_type,product|nullable|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'campaign_id' => 'required_if:item_type,campaign|nullable|exists:campaigns,id',
            'quantity' => 'nullable|integer|min:1|max:100',
            'amount' => 'nullable|numeric|min:1',
            // Dedication details (optional)
            'name' => 'nullable|string|max:100',
            'occasion' => 'nullable|string|max:100',
            'message' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($request, $validated): JsonResponse {
            $cart = $this->getOrCreateCart($request);

            $itemType = $validated['item_type'];
            $quantity = $validated['quantity'] ?? 1;

            // Handle different item types
            return match ($itemType) {
                'tree' => $this->addTreeToCart($cart, $validated),
                'product' => $this->addProductToCart(
                    $cart,
                    $validated,
                    $quantity,
                ),
                'campaign' => $this->addCampaignToCart(
                    $cart,
                    $validated,
                    $quantity,
                ),
                default => $this->error('Invalid item type', 422),
            };
        });
    }

    /**
     * @OA\Put(
     *     path="/api/v1/cart/items/{id}",
     *     summary="Update cart item quantity",
     *     description="Update the quantity of an item in the cart",
     *     tags={"Cart"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Cart Item ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"quantity"},
     *
     *             @OA\Property(property="quantity", type="integer", example=3, minimum=1, maximum=100, description="New quantity")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cart item updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cart item updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="cart", ref="#/components/schemas/Cart")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Cart item not found",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or insufficient stock"
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'nullable|integer|min:1|max:100',
            'name' => 'nullable|string|max:100',
            'occasion' => 'nullable|string|max:100',
            'message' => 'nullable|string|max:500',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        return DB::transaction(function () use ($request, $id, $validated): JsonResponse {
            $cart = $this->getOrCreateCart($request);

            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('id', $id)
                ->first();

            if (! $cartItem) {
                return $this->notFound('Cart item not found');
            }

            // Trees cannot have quantity > 1
            if (
                array_key_exists('quantity', $validated) &&
                $cartItem->cartable_type === TreeInstance::class &&
                $validated['quantity'] > 1
            ) {
                return $this->error(
                    'Tree items can only have quantity of 1',
                    422,
                );
            }

            // Check inventory for products
            if (
                in_array($cartItem->cartable_type, [
                    Product::class,
                    ProductVariant::class,
                ])
            ) {
                $product = $cartItem->cartable;
                $inventory = $product->inventory;

                // For product variants, check variant-level stock
                if ($cartItem->cartable_type === ProductVariant::class) {
                    $variant = $cartItem->cartable;
                    if (
                        array_key_exists('quantity', $validated) &&
                        (! $variant->is_instock || $variant->stock_quantity <= 0)
                    ) {
                        return $this->error(
                            'Product is out of stock',
                            422,
                        );
                    }

                    if (
                        array_key_exists('quantity', $validated) &&
                        $variant->stock_quantity < $validated['quantity']
                    ) {
                        return $this->error(
                            'Insufficient stock. Only '.
                                $variant->stock_quantity.
                                ' available',
                            422,
                        );
                    }
                } elseif ($inventory) {
                    // For regular products, check inventory-level stock
                    if (
                        array_key_exists('quantity', $validated) &&
                        (! $inventory->is_instock || $inventory->stock_quantity <= 0)
                    ) {
                        return $this->error(
                            'Product is out of stock',
                            422,
                        );
                    }

                    if (
                        array_key_exists('quantity', $validated) &&
                        $inventory->stock_quantity < $validated['quantity']
                    ) {
                        return $this->error(
                            'Insufficient stock. Only '.
                                $inventory->stock_quantity.
                                ' available',
                            422,
                        );
                    }
                }
            }

            // Update quantity if provided
            if (array_key_exists('quantity', $validated)) {
                // Double-check tree quantity rule
                if (
                    $cartItem->cartable_type === TreeInstance::class &&
                    (int) $validated['quantity'] > 1
                ) {
                    return $this->error(
                        'Tree items can only have quantity of 1',
                        422,
                    );
                }

                $cartItem->quantity = (int) $validated['quantity'];
            }

            // Update dedication details (for tree items)
            if ($cartItem->cartable_type === TreeInstance::class) {
                $options = $cartItem->options ?? [];
                $dedication = $options['dedication'] ?? [];

                if (array_key_exists('name', $validated)) {
                    $dedication['name'] = $validated['name'];
                }

                if (array_key_exists('occasion', $validated)) {
                    $dedication['occasion'] = $validated['occasion'];
                }

                if (array_key_exists('message', $validated)) {
                    $dedication['message'] = $validated['message'];
                }

                if (array_key_exists('location_id', $validated)) {
                    $dedication['location_id'] = $validated['location_id'];
                }

                $options['dedication'] = $dedication;
                $cartItem->options = $options;
            }

            $cartItem->save();

            return $this->loadCartAndRespond(
                $cart,
                'Cart item updated successfully',
            );
        });
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/cart/items/{id}",
     *     summary="Remove item from cart",
     *     description="Remove a specific item from the shopping cart",
     *     tags={"Cart"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Cart Item ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Item removed from cart",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Item removed from cart"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="cart", ref="#/components/schemas/Cart")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Cart item not found"
     *     )
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        return DB::transaction(function () use ($request, $id): JsonResponse {
            $cart = $this->getOrCreateCart($request);

            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('id', $id)
                ->first();

            if (! $cartItem) {
                return $this->notFound('Cart item not found');
            }

            $cartItem->delete();

            return $this->loadCartAndRespond($cart, 'Item removed from cart');
        });
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/cart",
     *     summary="Clear cart",
     *     description="Remove all items from the shopping cart",
     *     tags={"Cart"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cart cleared successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cart cleared successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="cart", ref="#/components/schemas/Cart")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function clear(Request $request): JsonResponse
    {
        return DB::transaction(function () use ($request): JsonResponse {
            $cart = $this->getOrCreateCart($request);

            $cart->items()->delete();

            return $this->success(
                [
                    'cart' => new CartResource($cart->fresh('items')),
                ],
                'Cart cleared successfully',
            );
        });
    }

    /**
     * Get or create user's cart
     */
    private function getOrCreateCart(Request $request): Cart
    {
        $user = $request->user();

        $cart = Cart::where('user_id', $user->id)
            ->where(function ($query): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $cart) {
            return Cart::create([
                'user_id' => $user->id,
                'expires_at' => now()->addDays(7),
            ]);
        }

        return $cart;
    }

    /**
     * Add tree to cart
     */
    private function addTreeToCart(
        Cart $cart,
        array $validated,
    ): JsonResponse {
        // Resolve tree instance: explicit tree_instance_id or find by tree_id + location_id
        $treeInstance = null;
        if (! empty($validated['tree_instance_id'])) {
            $treeInstance = TreeInstance::with('tree')->find(
                $validated['tree_instance_id'],
            );
        } else {
            $treeInstance = TreeInstance::with('tree')
                ->where('tree_id', $validated['tree_id'] ?? 0)
                ->where('location_id', $validated['location_id'] ?? 0)
                ->available()
                ->first();
        }

        if (! $treeInstance) {
            return $this->error(
                'No available tree found for the selected location',
                422,
            );
        }

        if ($treeInstance->status->value !== 'available') {
            return $this->error(
                'This tree is not available for sponsorship/adoption',
                422,
            );
        }

        // Verify tree plan price is active
        $treePlanPrice = TreePlanPrice::with(['plan', 'tree'])
            ->where('id', $validated['tree_plan_price_id'])
            ->where('is_active', true)
            ->first();

        if (! $treePlanPrice) {
            return $this->error('This pricing plan is not available', 422);
        }

        // Verify the plan belongs to the tree
        if ($treePlanPrice->tree_id !== $treeInstance->tree_id) {
            return $this->error('Invalid tree and plan combination', 422);
        }

        // Check if item already exists in cart
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('cartable_type', TreeInstance::class)
            ->where('cartable_id', $validated['tree_instance_id'])
            ->whereJsonContains(
                'options->tree_plan_price_id',
                $validated['tree_plan_price_id'],
            )
            ->first();

        if ($existingItem) {
            return $this->error(
                'This tree with the selected plan is already in your cart',
                422,
            );
        }

        // Create cart item with tree plan options
        CartItem::create([
            'cart_id' => $cart->id,
            'cartable_type' => TreeInstance::class,
            'cartable_id' => $validated['tree_instance_id'],
            'quantity' => 1, // Trees are always quantity 1
            'price' => $treePlanPrice->price,
            'options' => [
                'tree_plan_price_id' => $treePlanPrice->id,
                'plan_name' => $treePlanPrice->plan->name,
                'plan_type' => $treePlanPrice->plan->type->value,
                'duration' => $treePlanPrice->plan->duration,
                'duration_type' => $treePlanPrice->plan->duration_type->value,
                'duration_display' => $treePlanPrice->plan->duration.
                    ' '.
                    ucfirst((string) $treePlanPrice->plan->duration_type->value),
                'features' => $treePlanPrice->plan->features,
                'dedication' => [
                    'name' => $validated['name'] ?? null,
                    'occasion' => $validated['occasion'] ?? null,
                    'message' => $validated['message'] ?? null,
                    'location_id' => $validated['location_id'] ??
                        ($treeInstance->location_id ?? null),
                ],
            ],
        ]);

        return $this->loadCartAndRespond(
            $cart,
            'Tree added to cart successfully',
        );
    }

    /**
     * Add product to cart
     */
    private function addProductToCart(
        Cart $cart,
        array $validated,
        int $quantity,
    ): JsonResponse {
        // Determine if it's a product or product variant
        if (! empty($validated['product_variant_id'])) {
            $productVariant = ProductVariant::with(['inventory.product'])->find(
                $validated['product_variant_id'],
            );

            if (! $productVariant) {
                return $this->error('Product variant not found', 404);
            }

            $cartableType = ProductVariant::class;
            $cartableId = $productVariant->id;
            $price = $productVariant->price ?? 0;
            $sku = $productVariant->sku;

            // Check variant stock status
            if (! $productVariant->is_instock || $productVariant->stock_quantity <= 0) {
                return $this->error('Product is out of stock', 422);
            }

            if ($productVariant->stock_quantity < $quantity) {
                return $this->error(
                    'Insufficient stock. Only '.
                        $productVariant->stock_quantity.
                        ' available',
                    422,
                );
            }

            $options = [
                'variant' => [
                    'sku' => $productVariant->sku,
                    'color' => $productVariant->color,
                    'size' => $productVariant->size,
                ],
                'product_name' => $productVariant->inventory->product->name ?? 'Product',
            ];
        } else {
            $product = Product::with('inventory')->find(
                $validated['product_id'],
            );

            if (! $product) {
                return $this->error('Product not found', 404);
            }

            $cartableType = Product::class;
            $cartableId = $product->id;
            $price = $product->price ?? 0;
            $sku = $product->sku ?? null;

            // Check inventory
            if ($product->inventory) {
                if (! $product->inventory->is_instock || $product->inventory->stock_quantity <= 0) {
                    return $this->error('Product is out of stock', 422);
                }

                if ($product->inventory->stock_quantity < $quantity) {
                    return $this->error(
                        'Insufficient stock. Only '.
                            $product->inventory->stock_quantity.
                            ' available',
                        422,
                    );
                }
            }

            $options = [
                'product_name' => $product->name,
                'sku' => $sku,
            ];
        }

        // Check if item already exists in cart
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('cartable_type', $cartableType)
            ->where('cartable_id', $cartableId)
            ->first();

        if ($existingItem) {
            // Update quantity
            $existingItem->quantity += $quantity;
            $existingItem->save();

            return $this->loadCartAndRespond(
                $cart,
                'Cart item quantity updated',
            );
        }

        // Create new cart item
        CartItem::create([
            'cart_id' => $cart->id,
            'cartable_type' => $cartableType,
            'cartable_id' => $cartableId,
            'quantity' => $quantity,
            'price' => $price,
            'options' => $options,
        ]);

        return $this->loadCartAndRespond(
            $cart,
            'Product added to cart successfully',
        );
    }

    /**
     * Add campaign to cart
     */
    private function addCampaignToCart(
        Cart $cart,
        array $validated,
        int $quantity,
    ): JsonResponse {
        $campaign = Campaign::with('location')->find($validated['campaign_id']);

        if (! $campaign) {
            return $this->error('Campaign not found', 404);
        }

        if (! $campaign->is_active) {
            return $this->error('This campaign is not active', 422);
        }

        // Check if campaign has ended
        if ($campaign->end_date && $campaign->end_date->isPast()) {
            return $this->error('This campaign has ended', 422);
        }

        // Use custom amount if provided, otherwise use campaign's default amount
        $amount = $validated['amount'] ?? $campaign->amount;

        // Check if item already exists in cart
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('cartable_type', Campaign::class)
            ->where('cartable_id', $validated['campaign_id'])
            ->first();

        if ($existingItem) {
            // Update quantity
            $existingItem->quantity += $quantity;
            $existingItem->save();

            return $this->loadCartAndRespond(
                $cart,
                'Campaign contribution updated',
            );
        }

        // Create cart item
        CartItem::create([
            'cart_id' => $cart->id,
            'cartable_type' => Campaign::class,
            'cartable_id' => $validated['campaign_id'],
            'quantity' => $quantity,
            'price' => $amount,
            'options' => [
                'campaign_type' => $campaign->type->value,
                'campaign_type_label' => $campaign->type->label(),
                'location_id' => $campaign->location_id,
                'location_name' => $campaign->location->name ?? null,
                'description' => $campaign->description,
            ],
        ]);

        return $this->loadCartAndRespond(
            $cart,
            'Campaign added to cart successfully',
        );
    }

    /**
     * Helper method to load cart and return response
     */
    private function loadCartAndRespond(
        Cart $cart,
        string $message,
    ): JsonResponse {
        $cart->load([
            'items.cartable' => function ($query): void {
                $query->when($query->getModel() instanceof Product, function ($q): void {
                    $q->with([
                        'inventory.productVariants.variant.color',
                        'inventory.productVariants.variant.size',
                        'inventory.productVariants.variant.planter',
                        'productCategory',
                    ]);
                })->when($query->getModel() instanceof ProductVariant, function ($q): void {
                    $q->with('inventory.product');
                });
            },
        ]);

        return $this->success(
            [
                'cart' => new CartResource($cart),
            ],
            $message,
        );
    }
}
