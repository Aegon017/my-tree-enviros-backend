<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentFactory;
use Exception;
use Illuminate\Http\Request;

final class PaymentController extends Controller
{
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
