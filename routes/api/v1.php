<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AppDownloadController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BlogController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\FcmTokenController;
use App\Http\Controllers\Api\V1\GoogleAuthController;
use App\Http\Controllers\Api\V1\InitiativeController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\NotificationDeviceTokenController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaymentGatewayController;
use App\Http\Controllers\Api\V1\PaymentWebhookController;
use App\Http\Controllers\Api\V1\PhonePePaymentController;
use App\Http\Controllers\Api\V1\PostOfficeController;
use App\Http\Controllers\Api\V1\ProductCategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductReviewController;
use App\Http\Controllers\Api\V1\ReverseGeocodeController;
use App\Http\Controllers\Api\V1\ShippingAddressController;
use App\Http\Controllers\Api\V1\SliderController;
use App\Http\Controllers\Api\V1\TreeController;
use App\Http\Controllers\Api\V1\WishlistController;
use Illuminate\Support\Facades\Route;

Route::post('/sign-up', [AuthController::class, 'signUp']);
Route::post('/sign-in', [AuthController::class, 'signIn']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

Route::prefix('auth/google')->group(function (): void {
    Route::get('/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('/callback', [GoogleAuthController::class, 'callback']);
    Route::post('/mobile', [GoogleAuthController::class, 'mobileLogin']);
});

Route::prefix('/locations')->group(function (): void {
    Route::get('/', [LocationController::class, 'index']);
    Route::get('/root', [LocationController::class, 'root']);
    Route::get('/{id}', [LocationController::class, 'show']);
    Route::get('/{id}/children', [LocationController::class, 'children']);
    Route::get('/{id}/tree-count', [LocationController::class, 'treeCount']);
});

Route::prefix('/sliders')->group(function (): void {
    Route::get('/', [SliderController::class, 'index']);
    Route::get('/{id}', [SliderController::class, 'show']);
});

Route::prefix('/trees')->group(function (): void {
    Route::get('/', [TreeController::class, 'index']);
    Route::get('/{identifier}', [TreeController::class, 'show']);
});

Route::prefix('address')->group(function (): void {
    Route::get('reverse-geocode', [ReverseGeocodeController::class, 'show']);
    Route::get('post-offices', [PostOfficeController::class, 'index']);
});

Route::prefix('products')->group(function (): void {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/featured', [ProductController::class, 'featured']);
    Route::get('/{id}/reviews', [ProductReviewController::class, 'index']);

    Route::prefix('categories')->group(function (): void {
        Route::get('/', [ProductCategoryController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'byCategory']);
    });

    Route::get('/{identifier}', [ProductController::class, 'show']);
});

Route::prefix('/blogs')->group(function (): void {
    Route::get('/', [BlogController::class, 'index']);
    Route::get('/{identifier}', [BlogController::class, 'show']);
});

Route::prefix('campaigns')->group(function (): void {
    Route::get('/', [CampaignController::class, 'index']);
    Route::get('/{identifier}', [CampaignController::class, 'show']);
});

Route::prefix('initiatives')->group(function (): void {
    Route::get('/', [InitiativeController::class, 'index']);
});

// Payment callback route (for PhonePe redirects)
Route::get('payment/callback', [PaymentController::class, 'callback'])->name('api.v1.payment.callback');

Route::post('payments/webhook', [PaymentController::class, 'webhook'])->name('api.v1.payment.webhook');
Route::post('webhooks/razorpay', [PaymentWebhookController::class, 'razorpay'])->name('api.v1.webhooks.razorpay');
Route::post('webhooks/phonepe', [PaymentWebhookController::class, 'phonepe'])->name('api.v1.webhooks.phonepe');

// PhonePe Payment Routes
Route::prefix('payment/phonepe')->group(function (): void {
    Route::post('/webhook', [PhonePePaymentController::class, 'webhook'])->name('api.v1.phonepe.webhook');
    Route::middleware(['auth:sanctum'])->group(function (): void {
        Route::post('/token', [PhonePePaymentController::class, 'generateToken'])->name('api.v1.phonepe.generate-token');
        Route::post('/verify', [PhonePePaymentController::class, 'verifyPayment'])->name('api.v1.phonepe.verify');
        Route::get('/status/{orderId}', [PhonePePaymentController::class, 'getPaymentStatus'])->name('api.v1.phonepe.status');
        Route::post('/cancel', [PhonePePaymentController::class, 'cancelPayment'])->name('api.v1.phonepe.cancel');
    });
});

Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('me', [AuthController::class, 'updateProfile']);
    Route::post('sign-out', [AuthController::class, 'signOut']);

    Route::prefix('cart')->group(function (): void {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'store']);
        Route::put('/items/{id}', [CartController::class, 'update']);
        Route::delete('/items/{id}', [CartController::class, 'destroy']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    Route::get('/checkout', [CheckoutController::class, 'index']);
    Route::post('/checkout/prepare', [CheckoutController::class, 'prepare'])->name('checkout.prepare');
    Route::post('/checkout/verify', [PaymentController::class, 'verify'])->name('checkout.verify');
    Route::post('/checkout/check-coupon', [CheckoutController::class, 'checkCoupon'])->name('checkout.check-coupon');

    Route::prefix('orders')->group(function (): void {
        Route::get('/', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
        Route::get('/{order}/credit-note', [OrderController::class, 'creditNote'])->name('orders.credit-note');
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    });

    Route::get('my-trees', [OrderController::class, 'myTrees']);

    Route::prefix('products')->group(function (): void {
        Route::get('/category/{categoryId}', [ProductController::class, 'byCategory']);
        Route::get('/{id}/variants', [ProductController::class, 'variants']);
        Route::post('/{id}/reviews', [ProductReviewController::class, 'store']);
        Route::delete('/{id}/reviews/{reviewId}', [ProductReviewController::class, 'destroy']);
        Route::put('/{id}/reviews/{reviewId}', [ProductReviewController::class, 'update']);
    });

    Route::prefix('wishlist')->group(function (): void {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/items', [WishlistController::class, 'store']);
        Route::delete('/items/{id}', [WishlistController::class, 'destroy']);
        Route::delete('/', [WishlistController::class, 'clear']);
        Route::post('/items/{id}/move-to-cart', [WishlistController::class, 'moveToCart']);
    });

    Route::prefix('fcm-tokens')->group(function (): void {
        Route::get('/', [FcmTokenController::class, 'index']);
        Route::post('/', [FcmTokenController::class, 'store']);
        Route::delete('/{id}', [FcmTokenController::class, 'destroy']);
        Route::post('/delete-by-token', [FcmTokenController::class, 'destroyByToken']);
        Route::delete('/all', [FcmTokenController::class, 'destroyAll']);
    });

    Route::prefix('shipping-addresses')->group(function (): void {
        Route::get('/', [ShippingAddressController::class, 'index']);
        Route::post('/', [ShippingAddressController::class, 'store']);
        Route::get('/{id}', [ShippingAddressController::class, 'show']);
        Route::put('/{id}', [ShippingAddressController::class, 'update']);
        Route::delete('/{id}', [ShippingAddressController::class, 'destroy']);
        Route::post('/{id}/set-default', [ShippingAddressController::class, 'setDefault']);
    });

    Route::post('/device-tokens', [NotificationDeviceTokenController::class, 'store']);
    Route::delete('/device-tokens', [NotificationDeviceTokenController::class, 'destroy']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read', [NotificationController::class, 'markRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
});

Route::get('/payment-gateways', [PaymentGatewayController::class, 'index']);

Route::get('/get-app', [AppDownloadController::class, 'getApp'])->name('get-app');
Route::get('/app-settings', [AppDownloadController::class, 'getSettings'])->name('app-settings');
