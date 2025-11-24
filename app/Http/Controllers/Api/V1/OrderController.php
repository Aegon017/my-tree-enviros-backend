<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatusEnum;
use App\Enums\OrderTypeEnum;
use App\Enums\TreeStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TreeInstance;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Orders",
 *     description="Order management for trees, products, and campaigns"
 * )
 */
final class OrderController extends Controller
{
    use ResponseHelpers;

    /**
     * @OA\Get(
     *     path="/api/v1/orders",
     *     summary="List user's orders",
     *     description="Get paginated list of authenticated user's orders with optional filters",
     *     tags={"Orders"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by order status",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"pending", "paid", "failed", "success", "cancelled", "refunded", "completed"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by order type",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"sponsor", "adopt", "product", "campaign"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=15, maximum=50)
     *     ),
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
     *                 @OA\Property(
     *                     property="orders",
     *                     type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Order")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="total", type="integer")
     *                 )
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
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()
            ->with(['items.treeInstance.tree', 'items.planPrice.plan'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $perPage = min($request->input('per_page', 15), 50);
        $orders = $query->paginate($perPage);

        return $this->success([
            'orders' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders",
     *     summary="Create order from cart",
     *     description="Create a new order from items in the shopping cart",
     *     tags={"Orders"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="coupon_id", type="integer", example=1, description="Coupon ID for discount"),
     *             @OA\Property(property="shipping_address_id", type="integer", example=1, description="Shipping address ID (required for products)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order", ref="#/components/schemas/Order")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or cart is empty",
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
        Log::info($request->all());
        $validated = $request->validate([
            'coupon_id' => 'nullable|exists:coupons,id',
            'shipping_address_id' => 'nullable|exists:shipping_addresses,id',
        ]);

        return DB::transaction(function () use ($request, $validated): JsonResponse {
            $user = $request->user();

            // Get user's cart
            $cart = Cart::with([
                'items.treeInstance.tree',
                'items.planPrice.plan',
            ])
                ->where('user_id', $user->id)
                ->first();

            if (! $cart || $cart->items->isEmpty()) {
                return $this->error('Cart is empty', 422);
            }

            // Validate tree items are available
            foreach ($cart->items as $item) {
                if (
                    method_exists($item, 'isTree') &&
                    $item->isTree() &&
                    $item->treeInstance &&
                    $item->treeInstance->status->value !== 'available'
                ) {
                    return $this->error(
                        sprintf('Tree instance %s is no longer available', $item->treeInstance->sku),
                        422,
                    );
                }
            }

            // Determine order type based on cart items
            $orderType = $this->determineOrderType($cart);

            // Calculate totals
            $subtotal = $cart->totalAmount();
            $discountAmount = 0.0;
            $gstRate = 0.18; // 18% GST
            $gstAmount = round($subtotal * $gstRate, 2);
            $cgstAmount = round($gstAmount / 2, 2);
            $sgstAmount = round($gstAmount / 2, 2);
            $totalAmount = $subtotal - $discountAmount + $gstAmount;

            // Determine primary orderable (use first cart item) to satisfy non-null morph columns
            $firstItem = $cart->items->first();
            $primaryOrderableType = null;
            $primaryOrderableId = null;

            if ($firstItem) {
                if (method_exists($firstItem, 'isTree') && $firstItem->isTree()) {
                    $primaryOrderableType = TreeInstance::class;
                    $primaryOrderableId = $firstItem->tree_instance_id;
                } elseif (method_exists($firstItem, 'isProduct') && $firstItem->isProduct()) {
                    $primaryOrderableType = $firstItem->cartable_type;
                    $primaryOrderableId = $firstItem->cartable_id;
                } elseif (method_exists($firstItem, 'isCampaign') && $firstItem->isCampaign()) {
                    $primaryOrderableType = \App\Models\Campaign::class;
                    $primaryOrderableId = $firstItem->cartable_id;
                }
            }

            // Create order
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'type' => $orderType,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'gst_amount' => $gstAmount,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'status' => OrderStatusEnum::PENDING,
                'currency' => 'INR',
                'coupon_id' => $validated['coupon_id'] ?? null,
                'shipping_address_id' => $validated['shipping_address_id'] ?? null,
                'orderable_type' => $primaryOrderableType,
                'orderable_id' => $primaryOrderableId,
            ]);

            // Create order items from cart (trees, products, campaigns)
            foreach ($cart->items as $cartItem) {
                if (method_exists($cartItem, 'isTree') && $cartItem->isTree()) {
                    $plan = $cartItem->planPrice->plan;

                    // Calculate start and end dates based on plan duration
                    $startDate = now();
                    $endDate = $this->calculateEndDate(
                        $startDate,
                        $plan->duration,
                        $plan->duration_type->value,
                    );

                    OrderItem::create([
                        'order_id' => $order->id,
                        'tree_instance_id' => $cartItem->tree_instance_id,
                        'tree_plan_price_id' => $cartItem->tree_plan_price_id,
                        'price' => $cartItem->price,
                        'discount_amount' => 0.0,
                        'gst_amount' => round($cartItem->price * $gstRate, 2),
                        'cgst_amount' => round(
                            ($cartItem->price * $gstRate) / 2,
                            2,
                        ),
                        'sgst_amount' => round(
                            ($cartItem->price * $gstRate) / 2,
                            2,
                        ),
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'is_renewal' => false,
                        'options' => [
                            'plan_name' => $plan->name,
                            'plan_type' => $plan->type->value,
                            'duration_display' => $plan->duration.
                                ' '.
                                ucfirst((string) $plan->duration_type->value),
                            'dedication' => $cartItem->options['dedication'] ?? null,
                        ],
                    ]);

                    continue;
                }

                // Product items
                if (
                    method_exists($cartItem, 'isProduct') &&
                    $cartItem->isProduct()
                ) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'orderable_type' => $cartItem->cartable_type,
                        'orderable_id' => $cartItem->cartable_id,
                        'quantity' => $cartItem->quantity,
                        'price' => $cartItem->price,
                        'discount_amount' => 0.0,
                        'gst_amount' => round($cartItem->price * $gstRate, 2),
                        'cgst_amount' => round(
                            ($cartItem->price * $gstRate) / 2,
                            2,
                        ),
                        'sgst_amount' => round(
                            ($cartItem->price * $gstRate) / 2,
                            2,
                        ),
                        'is_renewal' => false,
                        'options' => $cartItem->options ?? [],
                    ]);

                    continue;
                }

                // Campaign items
                if (
                    method_exists($cartItem, 'isCampaign') &&
                    $cartItem->isCampaign()
                ) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'orderable_type' => \App\Models\Campaign::class,
                        'orderable_id' => $cartItem->cartable_id,
                        'quantity' => $cartItem->quantity,
                        'price' => $cartItem->price,
                        'discount_amount' => 0.0,
                        'gst_amount' => round($cartItem->price * $gstRate, 2),
                        'cgst_amount' => round(
                            ($cartItem->price * $gstRate) / 2,
                            2,
                        ),
                        'sgst_amount' => round(
                            ($cartItem->price * $gstRate) / 2,
                            2,
                        ),
                        'is_renewal' => false,
                        'options' => $cartItem->options ?? [],
                    ]);

                    continue;
                }
            }

            // Clear cart after order creation
            $cart->items()->delete();

            // Load order relationships
            $order->load([
                'items.treeInstance.tree',
                'items.planPrice.plan',
            ]);

            return $this->created(
                [
                    'order' => new OrderResource($order),
                ],
                'Order created successfully',
            );
        });
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/direct",
     *     summary="Direct purchase (buy now)",
     *     description="Create an order directly without adding to cart first",
     *     tags={"Orders"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"item_type"},
     *
     *             @OA\Property(property="item_type", type="string", enum={"tree", "product", "campaign"}, example="tree"),
     *             @OA\Property(property="tree_instance_id", type="integer", example=1, description="Required if item_type is 'tree'"),
     *             @OA\Property(property="tree_plan_price_id", type="integer", example=1, description="Required if item_type is 'tree'"),
     *             @OA\Property(property="product_id", type="integer", example=1, description="Required if item_type is 'product'"),
     *             @OA\Property(property="product_variant_id", type="integer", example=1, description="Optional for products"),
     *             @OA\Property(property="campaign_id", type="integer", example=1, description="Required if item_type is 'campaign'"),
     *             @OA\Property(property="quantity", type="integer", example=1, description="Quantity for products"),
     *             @OA\Property(property="coupon_id", type="integer", example=1, description="Coupon ID for discount"),
     *             @OA\Property(property="shipping_address_id", type="integer", example=1, description="Shipping address ID")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order", ref="#/components/schemas/Order")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or item not available"
     *     )
     * )
     */
    public function storeDirect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_type' => [
                'required',
                'string',
                \Illuminate\Validation\Rule::in(['tree', 'campaign']),
            ],
            'tree_instance_id' => 'required_if:item_type,tree|nullable|exists:tree_instances,id',
            'tree_plan_price_id' => 'required_if:item_type,tree|nullable|exists:tree_plan_prices,id',
            'coupon_id' => 'nullable|exists:coupons,id',
            'shipping_address_id' => 'nullable|exists:shipping_addresses,id',
            'campaign_id' => 'required_if:item_type,campaign|nullable|exists:campaigns,id',
            'amount' => 'required_if:item_type,campaign|nullable|numeric|min:1',
            'quantity' => 'nullable|integer|min:1|max:100',
            // Optional dedication details
            'name' => 'nullable|string|max:100',
            'occasion' => 'nullable|string|max:100',
            'message' => 'nullable|string|max:500',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        return DB::transaction(function () use ($request, $validated): JsonResponse {
            $user = $request->user();

            // Direct campaign contribution
            if (($validated['item_type'] ?? null) === 'campaign') {
                $campaign = \App\Models\Campaign::with('location')->find(
                    $validated['campaign_id'],
                );

                if (! $campaign) {
                    return $this->error('Campaign not found', 404);
                }

                if (! $campaign->is_active) {
                    return $this->error('This campaign is not active', 422);
                }

                if ($campaign->end_date && $campaign->end_date->isPast()) {
                    return $this->error('This campaign has ended', 422);
                }

                $quantity = $validated['quantity'] ?? 1;
                $amount =
                    (float) ($validated['amount'] ?? ($campaign->amount ?? 0));

                if ($amount <= 0) {
                    return $this->error('Invalid amount', 422);
                }

                // Calculate totals
                $subtotal = $amount * $quantity;
                $discountAmount = 0.0;
                $gstRate = 0.18; // 18% GST
                $gstAmount = round($subtotal * $gstRate, 2);
                $cgstAmount = round($gstAmount / 2, 2);
                $sgstAmount = round($gstAmount / 2, 2);
                $totalAmount = $subtotal - $discountAmount + $gstAmount;

                // Create order (set primary orderable to campaign)
                $order = Order::create([
                    'order_number' => $this->generateOrderNumber(),
                    'user_id' => $user->id,
                    'type' => OrderTypeEnum::CAMPAIGN,
                    'total_amount' => $totalAmount,
                    'discount_amount' => $discountAmount,
                    'gst_amount' => $gstAmount,
                    'cgst_amount' => $cgstAmount,
                    'sgst_amount' => $sgstAmount,
                    'status' => OrderStatusEnum::PENDING,
                    'currency' => 'INR',
                    'coupon_id' => $validated['coupon_id'] ?? null,
                    'shipping_address_id' => $validated['shipping_address_id'] ?? null,
                    'orderable_type' => \App\Models\Campaign::class,
                    'orderable_id' => $campaign->id,
                ]);

                // Create order item (campaign)
                OrderItem::create([
                    'order_id' => $order->id,
                    'orderable_type' => \App\Models\Campaign::class,
                    'orderable_id' => $campaign->id,
                    'quantity' => $quantity,
                    'price' => $amount,
                    'discount_amount' => 0.0,
                    'gst_amount' => round($amount * $gstRate, 2),
                    'cgst_amount' => round(($amount * $gstRate) / 2, 2),
                    'sgst_amount' => round(($amount * $gstRate) / 2, 2),
                    'is_renewal' => false,
                    'options' => [
                        'campaign_type' => $campaign->type?->value,
                        'campaign_type_label' => $campaign->type?->label(),
                        'location_id' => $campaign->location_id,
                        'location_name' => $campaign->location->name ?? null,
                        'description' => $campaign->description,
                    ],
                ]);

                $order->load(['items']);

                return $this->created(
                    [
                        'order' => new OrderResource($order),
                    ],
                    'Order created successfully',
                );
            }

            // Verify tree instance is available
            $treeInstance = TreeInstance::with('tree')->find(
                $validated['tree_instance_id'],
            );

            if (! $treeInstance) {
                return $this->error('Tree instance not found', 404);
            }

            if ($treeInstance->status !== TreeStatusEnum::AVAILABLE) {
                return $this->error('This tree is not available', 422);
            }

            // Get tree plan price
            $planPrice = \App\Models\planPrice::with(['plan', 'tree'])
                ->where('id', $validated['tree_plan_price_id'])
                ->where('is_active', true)
                ->first();

            if (! $planPrice) {
                return $this->error('This pricing plan is not available', 422);
            }

            // Verify the plan belongs to the tree
            if ($planPrice->tree_id !== $treeInstance->tree_id) {
                return $this->error('Invalid tree and plan combination', 422);
            }

            $plan = $planPrice->plan;
            $orderType =
                $plan->type->value === 'sponsorship'
                    ? OrderTypeEnum::SPONSOR
                    : OrderTypeEnum::ADOPT;

            // Calculate totals
            $subtotal = (float) $planPrice->price;
            $discountAmount = 0.0;
            $gstRate = 0.18; // 18% GST
            $gstAmount = round($subtotal * $gstRate, 2);
            $cgstAmount = round($gstAmount / 2, 2);
            $sgstAmount = round($gstAmount / 2, 2);
            $totalAmount = $subtotal - $discountAmount + $gstAmount;

            // Create order (set primary orderable to the tree instance)
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'type' => $orderType,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'gst_amount' => $gstAmount,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'status' => OrderStatusEnum::PENDING,
                'currency' => 'INR',
                'coupon_id' => $validated['coupon_id'] ?? null,
                'shipping_address_id' => $validated['shipping_address_id'] ?? null,
                'orderable_type' => TreeInstance::class,
                'orderable_id' => $treeInstance->id,
            ]);

            // Calculate start and end dates
            $startDate = now();
            $endDate = $this->calculateEndDate(
                $startDate,
                $plan->duration,
                $plan->duration_type->value,
            );

            // Create order item
            OrderItem::create([
                'order_id' => $order->id,
                'tree_instance_id' => $validated['tree_instance_id'],
                'tree_plan_price_id' => $validated['tree_plan_price_id'],
                'price' => $planPrice->price,
                'discount_amount' => 0.0,
                'gst_amount' => $gstAmount,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_renewal' => false,
                'options' => [
                    'plan_name' => $plan->name,
                    'plan_type' => $plan->type->value,
                    'duration_display' => $plan->duration.
                        ' '.
                        ucfirst((string) $plan->duration_type->value),
                    'dedication' => [
                        'name' => $validated['name'] ?? null,
                        'occasion' => $validated['occasion'] ?? null,
                        'message' => $validated['message'] ?? null,
                        'location_id' => $validated['location_id'] ?? null,
                    ],
                ],
            ]);

            // Load order relationships
            $order->load([
                'items.treeInstance.tree',
                'items.planPrice.plan',
            ]);

            return $this->created(
                [
                    'order' => new OrderResource($order),
                ],
                'Order created successfully',
            );
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{id}",
     *     summary="Get order details",
     *     description="Get detailed information about a specific order",
     *     tags={"Orders"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
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
     *                 @OA\Property(property="order", ref="#/components/schemas/Order")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $order = Order::with([
            'items.treeInstance.tree',
            'items.treeInstance.location',
            'items.planPrice.plan',
            'shippingAddress',
            'coupon',
        ])
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (! $order) {
            return $this->notFound('Order not found');
        }

        return $this->success([
            'order' => new OrderResource($order),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/my-trees",
     *     summary="Get my trees",
     *     description="Get all trees sponsored or adopted by the authenticated user",
     *     tags={"Orders"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by tree status",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"available", "sponsored", "adopted", "expired", "maintenance"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by order type",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"sponsor", "adopt"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=15, maximum=50)
     *     ),
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
     *                 @OA\Property(
     *                     property="trees",
     *                     type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/OrderItem")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="total", type="integer")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function myTrees(Request $request): JsonResponse
    {
        $query = OrderItem::query()
            ->with([
                'treeInstance.tree',
                'treeInstance.location',
                'planPrice.plan',
                'order',
            ])
            ->whereHas('order', function ($q) use ($request): void {
                $q->where('user_id', $request->user()->id)->whereIn('status', [
                    OrderStatusEnum::PAID->value,
                    OrderStatusEnum::SUCCESS->value,
                    OrderStatusEnum::COMPLETED->value,
                ]);
            })
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->whereHas('treeInstance', function ($q) use ($request): void {
                $q->where('status', $request->status);
            });
        }

        // Filter by type (sponsor/adopt)
        if ($request->has('type')) {
            $query->whereHas('order', function ($q) use ($request): void {
                $q->where('type', $request->type);
            });
        }

        $perPage = min($request->input('per_page', 15), 50);
        $orderItems = $query->paginate($perPage);

        return $this->success([
            'trees' => \App\Http\Resources\Api\V1\OrderItemResource::collection(
                $orderItems->items(),
            ),
            'meta' => [
                'current_page' => $orderItems->currentPage(),
                'last_page' => $orderItems->lastPage(),
                'per_page' => $orderItems->perPage(),
                'total' => $orderItems->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{id}/cancel",
     *     summary="Cancel order",
     *     description="Cancel a pending order",
     *     tags={"Orders"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Order cancelled successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order cancelled successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order", ref="#/components/schemas/Order")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Only pending orders can be cancelled"
     *     )
     * )
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        return DB::transaction(function () use ($request, $id): JsonResponse {
            $order = Order::where('user_id', $request->user()->id)->find($id);

            if (! $order) {
                return $this->notFound('Order not found');
            }

            if ($order->status !== OrderStatusEnum::PENDING) {
                return $this->error(
                    'Only pending orders can be cancelled',
                    422,
                );
            }

            $order->status = OrderStatusEnum::CANCELLED;
            $order->save();

            return $this->success(
                [
                    'order' => new OrderResource(
                        $order->load([
                            'items.treeInstance.tree',
                            'items.planPrice.plan',
                        ]),
                    ),
                ],
                'Order cancelled successfully',
            );
        });
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber(): string
    {
        do {
            $orderNumber =
                'ORD-'.
                mb_strtoupper(Str::random(3)).
                '-'.
                now()->format('Ymd').
                '-'.
                random_int(1000, 9999);
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Determine order type based on cart items
     */
    private function determineOrderType(Cart $cart): OrderTypeEnum
    {
        $hasTree = false;
        $hasProduct = false;
        $hasCampaign = false;

        $treePlanTypes = collect();

        foreach ($cart->items as $item) {
            if (method_exists($item, 'isTree') && $item->isTree()) {
                $hasTree = true;
                // Collect plan type for tree items (sponsorship/adoption)
                if ($item->planPrice && $item->planPrice->plan) {
                    $treePlanTypes->push(
                        $item->planPrice->plan->type->value,
                    );
                }

                continue;
            }

            if (method_exists($item, 'isProduct') && $item->isProduct()) {
                $hasProduct = true;

                continue;
            }

            if (method_exists($item, 'isCampaign') && $item->isCampaign()) {
                $hasCampaign = true;

                continue;
            }
        }

        // Single-category carts
        if ($hasCampaign && ! $hasTree && ! $hasProduct) {
            return OrderTypeEnum::CAMPAIGN;
        }

        if ($hasProduct && ! $hasTree && ! $hasCampaign) {
            return OrderTypeEnum::PRODUCT;
        }

        if ($hasTree && ! $hasProduct && ! $hasCampaign) {
            $treePlanTypes = $treePlanTypes->unique();

            if ($treePlanTypes->count() === 1) {
                return $treePlanTypes->first() === 'sponsorship'
                    ? OrderTypeEnum::SPONSOR
                    : OrderTypeEnum::ADOPT;
            }

            // Mixed tree plan types: default to sponsor
            return OrderTypeEnum::SPONSOR;
        }

        // Mixed-category carts (trees/products/campaigns): default to sponsor
        return OrderTypeEnum::SPONSOR;
    }

    /**
     * Calculate end date based on duration and type
     */
    private function calculateEndDate(
        \Carbon\CarbonInterface $startDate,
        int $duration,
        string $durationType,
    ): \Carbon\Carbon {
        $start = \Carbon\Carbon::parse($startDate);

        return match ($durationType) {
            'days' => $start->addDays($duration),
            'weeks' => $start->addWeeks($duration),
            'months' => $start->addMonths($duration),
            'years' => $start->addYears($duration),
            default => $start->addMonths($duration),
        };
    }
}
