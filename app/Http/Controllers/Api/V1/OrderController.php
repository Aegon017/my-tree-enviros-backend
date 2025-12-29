<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\InvoiceService;
use App\Services\CartService;
use App\Services\Orders\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelPdf\PdfBuilder;

final class OrderController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly OrderService $orderService,
        private readonly CartService $cartService
    ) {}

    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with([
                'items',
                'items.productVariant.inventory.product',
                'items.tree',
                'items.treeInstance.tree',
                'items.planPrice',
                'items.initiativeSite',
                'items.initiativeSite.location',
                'items.dedication',
                'items.campaign',
                'payment',
                'orderCharges',
                'shippingAddress'
            ])
            ->when($request->type, function ($query, $type) {
                $query->whereHas('items', function ($q) use ($type) {
                    $q->where('type', $type);
                });
            })
            ->latest()
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function show(Order $order): OrderResource
    {

        $order->load([
            'items.productVariant.inventory.product',
            'items.tree',
            'items.treeInstance.tree',
            'items.planPrice',
            'items.initiativeSite',
            'items.initiativeSite.location',
            'items.dedication',
            'items.campaign',
            'payment',
            'orderCharges',
            'shippingAddress',
        ]);

        return new OrderResource($order);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request): OrderResource {
            $user = $request->user();

            $order = $this->orderService->createDraftOrder([
                'items' => $request->items,
                'coupon_code' => $request->coupon_code,
                'payment_method' => $request->payment['method'] ?? null,
                'currency' => 'INR',
            ], $user->id);

            if ($request->payment) {
                $this->orderService->recordPayment($order, $request->payment);
                $this->cartService->clearUserCart($user->id);
            }

            $order->refresh()->load(['items', 'payment', 'orderCharges']);

            return new OrderResource($order);
        });
    }

    public function invoice(Order $order): PdfBuilder
    {

        if ($order->status !== OrderStatusEnum::PAID) {
            abort(403, 'Invoice is only available for paid orders.');
        }

        return $this->invoiceService->generate($order);
    }

    public function cancel(Request $request, Order $order): OrderResource
    {
        $request->validate([
            'reason' => 'required|string|min:5|max:255',
        ]);

        if ($order->status === OrderStatusEnum::COMPLETED || $order->status === OrderStatusEnum::CANCELLED || $order->status === OrderStatusEnum::REFUNDED) {
            abort(422, 'Cannot cancel a completed or already cancelled order.');
        }

        // Policy: "Pre-Shipment Cancellation: You may cancel your order for a full refund... provided the order has not yet been shipped."
        $order->update([
            'status' => OrderStatusEnum::CANCELLED,
            'cancellation_reason' => $request->reason,
        ]);

        // Send notifications to Customer and Admin
        $order->user->notify(new \App\Notifications\OrderCancelledNotification($order));

        // Notify Admin (Assuming Admin user exists or using route notification)
        // For now sending to a configurable admin email or route
        \Illuminate\Support\Facades\Notification::route('mail', config('mail.from.address'))
            ->notify(new \App\Notifications\OrderCancelledNotification($order));

        return new OrderResource($order);
    }

    public function creditNote(Order $order): PdfBuilder
    {
        // Credit Notes are typically for cancelled/refunded orders
        if (!in_array($order->status, [OrderStatusEnum::CANCELLED, OrderStatusEnum::REFUNDED])) {
            abort(403, 'Credit Note is only available for cancelled or refunded orders.');
        }

        return $this->invoiceService->generateCreditNote($order);
    }
}
