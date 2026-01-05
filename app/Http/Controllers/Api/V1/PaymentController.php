<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class PaymentController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate([
            'session_token' => 'required|string',
        ]);

        $sessionToken = $request->input('session_token');
        $session = CheckoutSession::where('session_token', $sessionToken)->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session token',
            ], 404);
        }

        if ($session->status === 'completed') {
            return response()->json([
                'success' => true,
                'status' => 'completed',
                'message' => 'Payment already verified',
                'session_token' => $sessionToken,
                'order_id' => $session->order_id,
            ]);
        }

        if ($request->has('razorpay_order_id')) {
            try {
                $attributes = [
                    'razorpay_order_id' => $request->input('razorpay_order_id'),
                    'razorpay_payment_id' => $request->input('razorpay_payment_id'),
                    'razorpay_signature' => $request->input('razorpay_signature'),
                ];

                $api = new \Razorpay\Api\Api(
                    config('services.razorpay.key'),
                    config('services.razorpay.secret')
                );
                $api->utility->verifyPaymentSignature($attributes);

                Log::info('Razorpay payment verified (client-side)', [
                    'session_token' => $sessionToken,
                    'payment_id' => $request->input('razorpay_payment_id'),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified. Order will be created via webhook.',
                    'session_token' => $sessionToken,
                ]);
            } catch (\Exception $e) {
                Log::error('Razorpay verification failed', [
                    'session_token' => $sessionToken,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed',
                ], 400);
            }
        }

        return response()->json([
            'success' => true,
            'status' => 'pending',
            'message' => 'Payment verification initiated. Awaiting webhook confirmation.',
            'session_token' => $sessionToken,
        ]);
    }
}
