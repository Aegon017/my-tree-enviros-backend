<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/sign-up', [AuthController::class, 'signUp']);
Route::post('/sign-in', [AuthController::class, 'signIn']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');

Route::middleware('auth:api')->group(function (): void {
    Route::get('/user', fn (Request $request) => response()->json($request->user()));

});
