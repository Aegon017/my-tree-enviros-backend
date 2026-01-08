<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use App\Services\Payments\PaymentFactory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class PaymentController extends Controller
{
    /**
     * Handle payment callback/redirect from payment gateways
     * This is where PhonePe redirects users after payment
     */
    public function callback(Request $request)
    {
        $frontendUrl = config('app.frontend_url');

        try {
            // Detect gateway
            $gateway = 'razorpay';
            if (
                $request->has('code') || $request->has('merchantId') || $request->has('merchantOrderId') ||
                ($request->has('transactionId') && str_starts_with($request->transactionId, 'MT-'))
            ) {
                $gateway = 'phonepe';
            }

            // Extract transaction ID
            $transactionId = $request->input('merchantTransactionId')
                ?? $request->input('merchantOrderId')
                ?? $request->input('transactionId');

            if (!$transactionId) {
                return redirect($frontendUrl . '/payment/failure?reason=missing_transaction_id');
            }

            // Verify payment
            $payload = $request->all();
            $payload['transaction_id'] = $transactionId;

            $result = PaymentFactory::driver($gateway)->verifyAndCapture($payload);

            if ($result['success'] ?? false) {
                // Redirect to success page with order ID
                return redirect($frontendUrl . '/payment/success?order_id=' . $result['order_id']);
            }

            return redirect($frontendUrl . '/payment/failure?reason=payment_verification_failed');
        } catch (Exception $e) {
            \Log::error('Payment callback error: ' . $e->getMessage());
            return redirect($frontendUrl . '/payment/failure?reason=' . urlencode($e->getMessage()));
        }
    }

    public function verify(Request $request)
    {
        // Support both old (session_token) and new (attempt_reference) flows
        $request->validate([
            'session_token' => 'required_without:attempt_reference|string',
            'attempt_reference' => 'required_without:session_token|string',
        ]);

        // New flow: Payment Attempt
        if ($request->has('attempt_reference')) {
            $attemptReference = $request->input('attempt_reference');
            $attempt = \App\Models\PaymentAttempt::where('attempt_reference', $attemptReference)->first();

            if (!$attempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid attempt reference',
                ], 404);
            }

            if ($attempt->status === 'completed' && $attempt->created_order_id) {
                return response()->json([
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Payment already verified',
                    'attempt_reference' => $attemptReference,
                    'order_id' => $attempt->created_order_id,
                ]);
            }

            // Handle Razorpay verification for payment attempts
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

                    Log::info('Razorpay payment verified for attempt', [
                        'attempt_reference' => $attemptReference,
                        'payment_id' => $request->input('razorpay_payment_id'),
                    ]);

                    // Convert attempt to order
                    $order = app(\App\Services\Payments\PaymentAttemptService::class)->convertToOrder($attempt);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment verified and order created',
                        'attempt_reference' => $attemptReference,
                        'order_id' => $order->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Razorpay verification failed for attempt', [
                        'attempt_reference' => $attemptReference,
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
                'message' => 'Payment verification initiated. Awaiting confirmation.',
                'attempt_reference' => $attemptReference,
            ]);
        }

        // Old flow: Checkout Session (for backward compatibility)
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
