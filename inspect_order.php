<?php

use App\Models\Order;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = Order::find(13);

if ($order) {
    echo "Order ID: " . $order->id . "\n";
    echo "Total Amount: " . $order->total_amount . "\n";
    echo "Status: " . $order->status->value . "\n";
} else {
    echo "Order 13 not found\n";
}
