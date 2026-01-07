<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\PaymentFactory;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class PhonePePaymentController extends Controller
{
    use ResponseHelpers;

    /**
     * Generate PhonePe payment token for mobile SDK
     */
    public function generateToken(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
                'amount' => 'required|integer|min:1',
                'merchant_transaction_id' => 'required|string',
                'user_id' => 'required|string',
                'user_mobile' => 'required|string|regex:/^[0-9]{10}$/',
            ]);

            $user = $request->user();

            // Verify order belongs to authenticated user
            $order = Order::where('id', $validated['order_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Use PaymentFactory instead of directly instantiating PhonepeService
            $phonePeService = PaymentFactory::driver('phonepe');

            // Generate token
            $token = $phonePeService->generateChecksum(
                $validated['merchant_transaction_id'],
                $validated['amount'],
                $validated['user_id'],
                $validated['user_mobile'],
                $validated['order_id']
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'merchant_transaction_id' => $validated['merchant_transaction_id'],
                    'amount' => $validated['amount'],
                    'currency' => 'INR',
                    'order_id' => $order->id,
                    'reference_number' => $order->reference_number,
                ],
                'message' => 'Token generated successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or does not belong to user'
            ], 404);
        } catch (\Exception $e) {
            Log::error('PhonePe Token Generation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate token: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verifyPayment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'merchant_transaction_id' => 'required|string',
                'phonepe_transaction_id' => 'nullable|string',
                'order_reference' => 'required|string|exists:orders,reference_number',
                'amount' => 'required|integer|min:1',
                'status' => 'required|in:SUCCESS,FAILED,PENDING',
            ]);

            $user = $request->user();

            // Find order by reference number
            $order = Order::where('reference_number', $validated['order_reference'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Verify payment with PhonePe API
            $phonePeService = PaymentFactory::driver('phonepe');

            $verificationResult = $phonePeService->verifyTransaction(
                $validated['merchant_transaction_id'],
                $order->id
            );

            if (!$verificationResult['success']) {
                return $this->error(
                    $verificationResult['message'] ?? 'Payment verification failed',
                    400
                );
            }

            // If payment status is failed, return error
            if ($validated['status'] === 'FAILED') {
                return $this->error('Payment was rejected by PhonePe', 400);
            }

            // Payment captured and order updated in verifyTransaction
            $order->refresh();

            return $this->success([
                'order_id' => $order->id,
                'reference_number' => $order->reference_number,
                'status' => $order->status,
                'amount' => $order->grand_total,
                'transaction_id' => $validated['phonepe_transaction_id'] ?? $validated['merchant_transaction_id'],
                'paid_at' => $order->paid_at?->toIso8601String(),
                'message' => 'Payment verified successfully',
            ]);
        } catch (ValidationException $e) {
            return $this->error(
                'Validation failed',
                422,
                $e->errors()
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Order not found', 404);
        } catch (\Exception $e) {
            return $this->error(
                'Payment verification failed: ' . $e->getMessage(),
                500
            );
        }
    }

    public function getPaymentStatus(Request $request, int $orderId): JsonResponse
    {
        try {
            $user = $request->user();

            $order = Order::where('id', $orderId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Get payment details
            $payment = $order->payments()->first();

            return $this->success([
                'order_id' => $order->id,
                'order_status' => $order->status,
                'payment_status' => $payment?->status ?? 'pending',
                'amount' => $order->grand_total,
                'transaction_id' => $payment?->transaction_id,
                'payment_method' => $payment?->payment_method,
                'paid_at' => $payment?->paid_at?->toIso8601String(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Order not found', 404);
        } catch (\Exception $e) {
            return $this->error(
                'Failed to fetch payment status: ' . $e->getMessage(),
                500
            );
        }
    }

    public function webhook(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();

            if (!$request->header('x-verify')) {
                return $this->error('Invalid request signature', 401);
            }

            // Verify and capture payment
            $phonePeService = PaymentFactory::driver('phonepe');
            $result = $phonePeService->verifyAndCapture($payload);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('PhonePe Webhook Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }

    public function cancelPayment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
                'merchant_transaction_id' => 'required|string',
                'reason' => 'nullable|string|max:500',
            ]);

            $user = $request->user();

            $order = Order::where('id', $validated['order_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Only allow cancellation if order is still pending
            if (!in_array($order->status, ['pending', 'draft'])) {
                return $this->error(
                    'Only pending or draft orders can be cancelled',
                    400
                );
            }

            $order->update([
                'status' => 'cancelled',
                'notes' => 'Payment cancelled by user. Reason: ' . ($validated['reason'] ?? 'Not specified'),
            ]);

            return $this->success([
                'order_id' => $order->id,
                'status' => $order->status,
                'message' => 'Payment cancelled successfully',
            ]);
        } catch (ValidationException $e) {
            return $this->error(
                'Validation failed',
                422,
                $e->errors()
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Order not found', 404);
        } catch (\Exception $e) {
            return $this->error(
                'Failed to cancel payment: ' . $e->getMessage(),
                500
            );
        }
    }
}
