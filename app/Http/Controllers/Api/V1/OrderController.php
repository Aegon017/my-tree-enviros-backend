<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\InvoiceService;
use App\Services\Orders\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class OrderController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly OrderService $orderService
    ) {}

    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with(['items', 'payment', 'orderCharges', 'shippingAddress'])
            ->latest()
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function show(Order $order): OrderResource
    {

        $order->load(['items.productVariant.product', 'items.tree', 'payment', 'orderCharges', 'shippingAddress']);

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
            }

            $order->refresh()->load(['items', 'payment', 'orderCharges']);

            return new OrderResource($order);
        });
    }

    public function invoice(Order $order): \Spatie\LaravelPdf\PdfBuilder
    {

        if ($order->status !== 'paid') {
            abort(403, 'Invoice is only available for paid orders.');
        }

        return $this->invoiceService->generate($order);
    }

    public function cancel(Order $order): OrderResource
    {

        if ($order->status === 'paid' || $order->status === 'completed') {
            abort(422, 'Cannot cancel a paid or completed order.');
        }

        $order->update(['status' => 'cancelled']);

        return new OrderResource($order);
    }
}
