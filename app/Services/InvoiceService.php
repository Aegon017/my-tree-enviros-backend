<?php

namespace App\Services;

use App\Models\Order;
use Spatie\LaravelPdf\Facades\Pdf;

class InvoiceService
{
    public function generate(Order $order)
    {
        $order->load(['items.tree', 'items.planPrice.plan', 'user', 'shippingAddress']);

        return Pdf::view('invoices.order', ['order' => $order])
            ->name('invoice-' . $order->reference_number . '.pdf');
    }
}
