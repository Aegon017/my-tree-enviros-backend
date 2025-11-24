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
use App\Models\ProductVariant;
use App\Models\Tree;
use App\Models\TreeInstance;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class OrderController extends Controller
{
    use ResponseHelpers;

    public function index(Request $request): JsonResponse
    {
        $query = Order::query()
            ->with(['items.treeInstance.tree', 'items.planPrice.plan'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

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

    public function store(Request $request): JsonResponse
    {
        Log::info($request->all());

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

            $subtotal = $cart->totalAmount();
            $discount = 0.0;
            $gstRate = 0.18;
            $gstAmount = round($subtotal * $gstRate, 2);
            $cgstAmount = round($gstAmount / 2, 2);
            $sgstAmount = round($gstAmount / 2, 2);
            $total = $subtotal - $discount + $gstAmount;

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

            foreach ($cart->items as $cartItem) {
                if (method_exists($cartItem, 'isTree') && $cartItem->isTree()) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'type' => $cartItem->type,
                        'tree_id' => $cartItem->tree_id,
                        'plan_id' => $cartItem->plan_id,
                        'plan_price_id' => $cartItem->plan_price_id,
                        'quantity' => $cartItem->quantity ?? 1,
                        'amount' => $cartItem->amount,
                        'total_amount' => $cartItem->total_amount,
                    ]);

                    continue;
                }

                if (method_exists($cartItem, 'isProduct') && $cartItem->isProduct()) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'type' => $cartItem->type,
                        'product_variant_id' => $cartItem->product_variant_id,
                        'quantity' => $cartItem->quantity,
                        'amount' => $cartItem->amount,
                        'total_amount' => $cartItem->total_amount,
                    ]);

                    continue;
                }
            }

            $cart->items()->delete();

            $order->load([
                'items.tree',
                'items.planPrice.plan',
                'items.orderable',
            ]);

            return $this->created(
                [
                    'order' => new OrderResource($order),
                ],
                'Order created successfully',
            );
        });
    }

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
            'name' => 'nullable|string|max:100',
            'occasion' => 'nullable|string|max:100',
            'message' => 'nullable|string|max:500',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        return DB::transaction(function () use ($request, $validated): JsonResponse {
            $user = $request->user();

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
                $amount = (float) ($validated['amount'] ?? ($campaign->amount ?? 0));

                if ($amount <= 0) {
                    return $this->error('Invalid amount', 422);
                }

                $subtotal = $amount * $quantity;
                $discount = 0.0;
                $gstRate = 0.18;
                $gstAmount = round($subtotal * $gstRate, 2);
                $cgstAmount = round($gstAmount / 2, 2);
                $sgstAmount = round($gstAmount / 2, 2);
                $total = $subtotal - $discount + $gstAmount;

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

                OrderItem::create([
                    'order_id' => $order->id,
                    'type' => 'campaign',
                    'quantity' => $quantity,
                    'amount' => $amount,
                    'total_amount' => $amount * $quantity,
                ]);

                $order->load(['items']);

                return $this->created(
                    [
                        'order' => new OrderResource($order),
                    ],
                    'Order created successfully',
                );
            }

            $treeInstance = TreeInstance::with('tree')->find(
                $validated['tree_instance_id'],
            );

            if (! $treeInstance) {
                return $this->error('Tree instance not found', 404);
            }

            if ($treeInstance->status !== TreeStatusEnum::ADOPTABLE) {
                return $this->error('This tree is not available', 422);
            }

            $planPrice = \App\Models\planPrice::with(['plan', 'tree'])
                ->where('id', $validated['tree_plan_price_id'])
                ->where('is_active', true)
                ->first();

            if (! $planPrice) {
                return $this->error('This pricing plan is not available', 422);
            }

            if ($planPrice->tree_id !== $treeInstance->tree_id) {
                return $this->error('Invalid tree and plan combination', 422);
            }

            $plan = $planPrice->plan;
            $planType = ($plan->type?->value === 'sponsorship') ? 'sponsor' : 'adopt';

            $subtotal = (float) $planPrice->price;
            $discount = 0.0;
            $gstRate = 0.18;
            $gstAmount = round($subtotal * $gstRate, 2);
            $cgstAmount = round($gstAmount / 2, 2);
            $sgstAmount = round($gstAmount / 2, 2);
            $total = $subtotal - $discount + $gstAmount;

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

            OrderItem::create([
                'order_id' => $order->id,
                'type' => $planType,
                'tree_instance_id' => $validated['tree_instance_id'],
                'tree_id' => $treeInstance->tree_id,
                'plan_id' => $plan->id,
                'plan_price_id' => $validated['tree_plan_price_id'],
                'quantity' => 1,
                'amount' => $planPrice->price,
                'total_amount' => $planPrice->price,
            ]);

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

        if ($request->has('status')) {
            $query->whereHas('treeInstance', function ($q) use ($request): void {
                $q->where('status', $request->status);
            });
        }

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

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber =
                'ORD-' .
                mb_strtoupper(Str::random(3)) .
                '-' .
                now()->format('Ymd') .
                '-' .
                random_int(1000, 9999);
        } while (Order::where('reference_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
