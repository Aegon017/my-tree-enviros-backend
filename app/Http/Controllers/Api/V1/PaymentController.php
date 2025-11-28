<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentFactory;
use Illuminate\Http\Request;

final class PaymentController extends Controller
{
    public function verify(Request $request)
    {
        $result = PaymentFactory::driver('razorpay')->verifyAndCapture([
            'razorpay_order_id' => $request->razorpay_order_id,
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_signature' => $request->razorpay_signature,
            'order_reference' => $request->order_reference,
        ]);

        return response()->json($result);
    }
}
