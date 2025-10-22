<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\FcmTokenController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\TreeController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('sign-up', [AuthController::class, 'signUp']);
Route::post('sign-in', [AuthController::class, 'signIn']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('resend-otp', [AuthController::class, 'resendOtp']);

// Public tree browsing
Route::prefix('trees')->group(function () {
    Route::get('/', [TreeController::class, 'index']);
    Route::get('/sponsorship', [TreeController::class, 'sponsorship']);
    Route::get('/adoption', [TreeController::class, 'adoption']);
    Route::get('/{id}', [TreeController::class, 'show']);
    Route::get('/{id}/plans', [TreeController::class, 'plans']);
});

// Razorpay webhook (public)
Route::post('payments/webhook/razorpay', [PaymentController::class, 'webhook']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    // Users
    Route::apiResource('users', UserController::class);

    // Cart Management
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'store']);
        Route::put('/items/{id}', [CartController::class, 'update']);
        Route::delete('/items/{id}', [CartController::class, 'destroy']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    // Order Management
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::post('/direct', [OrderController::class, 'storeDirect']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
    });

    // My Trees (Sponsored/Adopted)
    Route::get('my-trees', [OrderController::class, 'myTrees']);

    // Payment
    Route::prefix('orders/{orderId}/payment')->group(function () {
        Route::post('/initiate', [PaymentController::class, 'initiateRazorpay']);
        Route::post('/verify', [PaymentController::class, 'verifyRazorpay']);
        Route::get('/status', [PaymentController::class, 'status']);
    });

    // Products
    Route::prefix('products')->group(function () {
        Route::get('/featured', [\App\Http\Controllers\Api\V1\ProductController::class, 'featured']);
        Route::get('/category/{categoryId}', [\App\Http\Controllers\Api\V1\ProductController::class, 'byCategory']);
        Route::get('/{id}/variants', [\App\Http\Controllers\Api\V1\ProductController::class, 'variants']);
    });

    // Wishlist
    Route::prefix('wishlist')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\WishlistController::class, 'index']);
        Route::post('/items', [\App\Http\Controllers\Api\V1\WishlistController::class, 'store']);
        Route::delete('/items/{id}', [\App\Http\Controllers\Api\V1\WishlistController::class, 'destroy']);
        Route::delete('/', [\App\Http\Controllers\Api\V1\WishlistController::class, 'clear']);
        Route::post('/items/{id}/move-to-cart', [\App\Http\Controllers\Api\V1\WishlistController::class, 'moveToCart']);
        Route::get('/check/{productId}', [\App\Http\Controllers\Api\V1\WishlistController::class, 'check']);
    });

    // FCM Token Management
    Route::prefix('fcm-tokens')->group(function () {
        Route::get('/', [FcmTokenController::class, 'index']);
        Route::post('/', [FcmTokenController::class, 'store']);
        Route::delete('/{id}', [FcmTokenController::class, 'destroy']);
        Route::post('/delete-by-token', [FcmTokenController::class, 'destroyByToken']);
        Route::delete('/all', [FcmTokenController::class, 'destroyAll']);
    });
});

// Public product routes
Route::prefix('products')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\V1\ProductController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\Api\V1\ProductController::class, 'show']);
});
