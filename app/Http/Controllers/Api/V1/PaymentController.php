<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentFactory;
use Exception;
use Illuminate\Http\Request;

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
        $gateway = 'razorpay';

        if ($request->has('code') || $request->has('merchantId') || $request->has('merchantOrderId') || ($request->has('transaction_id') && str_starts_with($request->transaction_id, 'MT-'))) {
            $gateway = 'phonepe';
        }

        $payload = $request->all();
        $result = PaymentFactory::driver($gateway)->verifyAndCapture($payload);

        return response()->json($result);
    }

    public function webhook(Request $request)
    {
        if ($request->has('response') && $request->header('x-verify')) {
            $gateway = 'phonepe';
        } elseif ($request->has('razorpay_payment_id')) {
            $gateway = 'razorpay';
        } else {
            return response()->json(['message' => 'Unknown gateway'], 400);
        }

        try {
            if ($gateway === 'phonepe') {
                $responseStr = $request->input('response');
                $decoded = json_decode(base64_decode((string) $responseStr), true);
                $payload = [
                    'transaction_id' => $decoded['data']['merchantTransactionId'] ?? null,
                    'raw_response' => $decoded,
                    'is_webhook' => true,
                ];
                if ($payload['transaction_id']) {
                    PaymentFactory::driver('phonepe')->verifyAndCapture($payload);
                }

                return response()->json(['success' => true]);
            }

            PaymentFactory::driver($gateway)->verifyAndCapture($request->all());

            return response()->json(['success' => true]);
        } catch (Exception $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }
}
