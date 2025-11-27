<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatusEnum;
use App\Enums\TreeStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderItemResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Campaign;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlanPrice;
use App\Models\TreeInstance;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class OrderController extends Controller
{
    use ResponseHelpers;

    /**
     * List user's orders
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()
            ->with(['items.treeInstance.tree', 'items.planPrice.plan', 'items.treeInstance.location'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min((int) $request->input('per_page', 15), 50);
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
     * Create order from cart
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coupon_id' => 'nullable|exists:coupons,id',
            'shipping_address_id' => 'nullable|exists:shipping_addresses,id',
        ]);

        return DB::transaction(function () use ($request, $validated): JsonResponse {
            $user = $request->user();

            $cart = Cart::with([
                'items.tree.planPrices.plan',
                'items.planPrice.plan',
                'items.productVariant.inventory.product',
            ])
                ->where('user_id', $user->id)
                ->first();

            if (! $cart || $cart->items->isEmpty()) {
                return $this->error('Cart is empty', 422);
            }

            // Calculate totals
            $subtotal = $cart->totalAmount();
            $discount = 0.0; // Implement coupon logic here if needed
            $gstRate = 0.18;
            $gstAmount = round($subtotal * $gstRate, 2);
            $cgstAmount = round($gstAmount / 2, 2);
            $sgstAmount = round($gstAmount / 2, 2);
            $total = $subtotal - $discount + $gstAmount;

            // Create Order
            $order = Order::create([
                'reference_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'gst_amount' => $gstAmount,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'total' => $total,
                'status' => OrderStatusEnum::PENDING->value,
                'currency' => 'INR',
                'coupon_id' => $validated['coupon_id'] ?? null,
                'shipping_address_id' => $validated['shipping_address_id'] ?? null,
                'payment_method' => null, // Will be set upon payment
            ]);

            // Create Order Items
            foreach ($cart->items as $cartItem) {
                $orderItemData = [
                    'order_id' => $order->id,
                    'type' => $cartItem->type,
                    'quantity' => $cartItem->quantity,
                    'amount' => $cartItem->amount,
                    'total_amount' => $cartItem->total_amount,
                ];

                if ($cartItem->tree_id) {
                    $orderItemData['tree_id'] = $cartItem->tree_id;
                    $orderItemData['plan_id'] = $cartItem->plan_id;
                    $orderItemData['plan_price_id'] = $cartItem->plan_price_id;
                    $orderItemData['tree_instance_id'] = $cartItem->tree_instance_id; // If assigned in cart
                } elseif ($cartItem->product_variant_id) {
                    $orderItemData['product_variant_id'] = $cartItem->product_variant_id;
                }

                OrderItem::create($orderItemData);
            }

            // Clear Cart
            $cart->items()->delete();

            return $this->created(
                ['order' => new OrderResource($order->load(['items.tree', 'items.planPrice.plan']))],
                'Order created successfully'
            );
        });
    }

    /**
     * Create direct order (Buy Now)
     */
    public function storeDirect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_type' => ['required', 'string', Rule::in(['tree', 'campaign'])],
            // Tree validation
            'tree_instance_id' => 'required_if:item_type,tree|nullable|exists:tree_instances,id',
            'tree_plan_price_id' => 'required_if:item_type,tree|nullable|exists:plan_prices,id',
            // Campaign validation
            'campaign_id' => 'required_if:item_type,campaign|nullable|exists:campaigns,id',
            'amount' => 'required_if:item_type,campaign|nullable|numeric|min:1',
            // Common
            'quantity' => 'nullable|integer|min:1|max:100',
            'coupon_id' => 'nullable|exists:coupons,id',
            'shipping_address_id' => 'nullable|exists:shipping_addresses,id',
        ]);

        return DB::transaction(function () use ($request, $validated): JsonResponse {
            $user = $request->user();
            $itemType = $validated['item_type'];
            $quantity = $validated['quantity'] ?? 1;

            $subtotal = 0.0;
            $orderItemData = [];

            if ($itemType === 'campaign') {
                $campaign = Campaign::find($validated['campaign_id']);

                if (! $campaign->is_active || ($campaign->end_date && $campaign->end_date->isPast())) {
                    return $this->error('Campaign is not active or has ended', 422);
                }

                $amount = (float) ($validated['amount'] ?? $campaign->amount);
                if ($amount <= 0) {
                    return $this->error('Invalid amount', 422);
                }

                $subtotal = $amount * $quantity;

                $orderItemData = [
                    'type' => 'campaign',
                    'quantity' => $quantity,
                    'amount' => $amount,
                    'total_amount' => $subtotal,
                    // Store campaign reference if needed, currently schema doesn't have campaign_id in order_items
                    // Assuming campaign orders are tracked via type or other means, or we might need to add campaign_id to order_items
                ];
            } elseif ($itemType === 'tree') {
                $treeInstance = TreeInstance::find($validated['tree_instance_id']);

                if ($treeInstance->status !== TreeStatusEnum::ADOPTABLE) {
                    return $this->error('Tree is not available', 422);
                }

                $planPrice = PlanPrice::with('plan')->find($validated['tree_plan_price_id']);

                if (! $planPrice || $planPrice->tree_id !== $treeInstance->tree_id) {
                    return $this->error('Invalid plan for this tree', 422);
                }

                $subtotal = (float) $planPrice->price * $quantity;
                $planType = $planPrice->plan->type->value === 'sponsorship' ? 'sponsor' : 'adopt';

                $orderItemData = [
                    'type' => $planType,
                    'tree_instance_id' => $treeInstance->id,
                    'tree_id' => $treeInstance->tree_id,
                    'plan_id' => $planPrice->plan_id,
                    'plan_price_id' => $planPrice->id,
                    'quantity' => $quantity,
                    'amount' => $planPrice->price,
                    'total_amount' => $subtotal,
                ];
            }

            // Calculate Taxes
            $discount = 0.0;
            $gstRate = 0.18;
            $gstAmount = round($subtotal * $gstRate, 2);
            $cgstAmount = round($gstAmount / 2, 2);
            $sgstAmount = round($gstAmount / 2, 2);
            $total = $subtotal - $discount + $gstAmount;

            // Create Order
            $order = Order::create([
                'reference_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'gst_amount' => $gstAmount,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'total' => $total,
                'status' => OrderStatusEnum::PENDING->value,
                'currency' => 'INR',
                'coupon_id' => $validated['coupon_id'] ?? null,
                'shipping_address_id' => $validated['shipping_address_id'] ?? null,
            ]);

            // Create Order Item
            $orderItemData['order_id'] = $order->id;
            OrderItem::create($orderItemData);

            return $this->created(
                ['order' => new OrderResource($order->load(['items.treeInstance.tree', 'items.planPrice.plan']))],
                'Order created successfully'
            );
        });
    }

    /**
     * Show order details
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

        return $this->success(['order' => new OrderResource($order)]);
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        return DB::transaction(function () use ($request, $id): JsonResponse {
            $order = Order::where('user_id', $request->user()->id)->find($id);

            if (! $order) {
                return $this->notFound('Order not found');
            }

            if ($order->status !== OrderStatusEnum::PENDING) {
                return $this->error('Only pending orders can be cancelled', 422);
            }

            $order->status = OrderStatusEnum::CANCELLED;
            $order->save();

            return $this->success(
                ['order' => new OrderResource($order)],
                'Order cancelled successfully'
            );
        });
    }

    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $coupon = \App\Models\Coupon::where('code', $request->code)->first();

        if (! $coupon) {
            return $this->error('Invalid coupon code', 404);
        }

        if (! $coupon->is_active) {
            return $this->error('Coupon is not active', 422);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return $this->error('Coupon has expired', 422);
        }

        $discount = $coupon->type === 'percentage' ? ($request->amount * $coupon->value) / 100 : $coupon->value;

        // Cap discount if needed (e.g., max_discount column)
        // if ($coupon->max_discount && $discount > $coupon->max_discount) {
        //     $discount = $coupon->max_discount;
        // }

        return $this->success([
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'discount' => $discount,
        ], 'Coupon applied successfully');
    }

    /**
     * Get user's trees (from successful orders)
     */
    public function myTrees(Request $request): JsonResponse
    {
        $query = OrderItem::query()
            ->with(['treeInstance.tree', 'treeInstance.location', 'planPrice.plan', 'order'])
            ->whereHas('order', function ($q) use ($request): void {
                $q->where('user_id', $request->user()->id)
                    ->whereIn('status', [OrderStatusEnum::PAID->value, OrderStatusEnum::SUCCESS->value, OrderStatusEnum::COMPLETED->value]);
            })
            ->whereNotNull('tree_instance_id')
            ->orderBy('created_at', 'desc');

        $perPage = min((int) $request->input('per_page', 15), 50);
        $items = $query->paginate($perPage);

        return $this->success([
            'trees' => OrderItemResource::collection($items->items()),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-'.mb_strtoupper(Str::random(3)).'-'.now()->format('Ymd').'-'.random_int(1000, 9999);
        } while (Order::where('reference_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
