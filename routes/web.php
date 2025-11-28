<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\OrderController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'admin');

Route::prefix('orders')->name('orders.')->group(function (): void {
    Route::get('/{order}/invoice', [OrderController::class, 'generateInvoice'])->name('invoice');
});
