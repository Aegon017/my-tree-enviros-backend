<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InvoiceService;

class OrderController extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function generateInvoice(Order $order)
    {
        return $this->invoiceService->generate($order);
    }
}
