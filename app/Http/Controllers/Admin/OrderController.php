<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InvoiceService;

final class OrderController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function generateInvoice(Order $order): \Spatie\LaravelPdf\PdfBuilder
    {
        return $this->invoiceService->generate($order);
    }
}
