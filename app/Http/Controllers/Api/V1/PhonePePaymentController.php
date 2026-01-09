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
     * Create Order Token for SDK integration (React Native / Web)
     */
    public function createOrderToken(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
                'amount' => 'required|integer|min:100',
            ]);

            $user = $request->user();

            // Verify order belongs to authenticated user
            $order = Order::where('id', $validated['order_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Use PaymentFactory to get PhonePe service
            $phonePeService = PaymentFactory::driver('phonepe');

            // Create order token
            $result = $phonePeService->createOrderToken(
                $order->id,
                $validated['amount']
            );

            if (!$result['success']) {
                return $this->error(
                    $result['message'] ?? 'Failed to create order token',
                    500
                );
            }

            return $this->success([
                'orderId' => $result['orderId'],
                'merchantOrderId' => $result['merchantOrderId'],
                'token' => $result['token'],
                'state' => $result['state'],
                'expireAt' => $result['expireAt'],
                'amount' => $validated['amount'],
                'currency' => 'INR',
            ]);
        } catch (ValidationException $e) {
            return $this->error(
                'Validation failed',
                422,
                $e->errors()
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Order not found or does not belong to user', 404);
        } catch (\Exception $e) {
            return $this->error(
                'Failed to create order token: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Legacy method - Generate token for old mobile app integration
     * Works with payment-first architecture using PaymentAttempt
     */
    public function generateToken(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_id' => 'nullable|integer', // This might be attempt ID in new flow
                'amount' => 'required|integer|min:100',
                'merchant_transaction_id' => 'required|string',
                'user_id' => 'nullable|string',
                'user_mobile' => 'nullable|string',
            ]);

            $user = $request->user();

            // Use PaymentFactory to get PhonePe service
            $phonePeService = PaymentFactory::driver('phonepe');

            // Generate merchant order ID from the transaction ID
            $merchantOrderId = $validated['merchant_transaction_id'];

            // Create payload for PhonePe SDK
            // SDK expects 'orderId' field (not 'merchantOrderId')
            $payload = [
                'orderId' => $merchantOrderId,
                'amount' => $validated['amount'],
                'paymentFlow' => [
                    'type' => 'PG_CHECKOUT',
                ],
            ];

            // Generate base64 encoded payload
            $base64Payload = base64_encode(json_encode($payload));

            // Generate checksum (SHA256 hash)
            $saltKey = config('services.phonepe.client_secret');
            $saltIndex = config('services.phonepe.client_version', 1);

            // For SDK flow, we don't include the endpoint in the checksum calculation
            // The SDK will handle the API call, and we just need to provide a valid checksum
            // that validates the body + salt
            $stringToHash = $base64Payload . '/pg/v1/pay' . $saltKey;
            $sha256Hash = hash('sha256', $stringToHash);
            $checksum = $sha256Hash . '###' . $saltIndex;

            // Combine into final token
            $token = $base64Payload . '###' . $checksum;

            return $this->success([
                'token' => $token,
                'merchant_transaction_id' => $merchantOrderId,
                'amount' => $validated['amount'],
                'currency' => 'INR',
            ]);
        } catch (ValidationException $e) {
            return $this->error(
                'Validation failed',
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            Log::error('PhonePe generateToken error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error(
                'Failed to generate PhonePe token: ' . $e->getMessage(),
                500
            );
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

    /**
     * Check Order Status by merchant order ID
     */
    public function checkOrderStatus(Request $request, string $merchantOrderId): JsonResponse
    {
        try {
            $user = $request->user();

            $phonePeService = PaymentFactory::driver('phonepe');
            $result = $phonePeService->checkOrderStatus($merchantOrderId, false, true);

            if (!$result['success']) {
                return $this->error(
                    $result['message'] ?? 'Failed to check order status',
                    $result['status_code'] ?? 500
                );
            }

            return $this->success($result['data']);
        } catch (\Exception $e) {
            return $this->error(
                'Failed to check order status: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Initiate Refund
     */
    public function initiateRefund(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
                'merchant_order_id' => 'required|string',
                'amount' => 'required|integer|min:100',
                'reason' => 'nullable|string|max:500',
            ]);

            $user = $request->user();

            $order = Order::where('id', $validated['order_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Only allow refund if order is paid
            if ($order->status !== 'paid') {
                return $this->error(
                    'Only paid orders can be refunded',
                    400
                );
            }

            $merchantRefundId = 'REFUND-' . time() . '-' . $order->id;

            $phonePeService = PaymentFactory::driver('phonepe');
            $result = $phonePeService->initiateRefund(
                $merchantRefundId,
                $validated['merchant_order_id'],
                $validated['amount']
            );

            if (!$result['success']) {
                return $this->error(
                    $result['message'] ?? 'Failed to initiate refund',
                    500
                );
            }

            return $this->success([
                'refundId' => $result['refundId'],
                'merchantRefundId' => $merchantRefundId,
                'amount' => $result['amount'],
                'state' => $result['state'],
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
                'Failed to initiate refund: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get Refund Status
     */
    public function getRefundStatus(Request $request, string $merchantRefundId): JsonResponse
    {
        try {
            $user = $request->user();

            $phonePeService = PaymentFactory::driver('phonepe');
            $result = $phonePeService->checkRefundStatus($merchantRefundId);

            if (!$result['success']) {
                return $this->error(
                    $result['message'] ?? 'Failed to check refund status',
                    $result['status_code'] ?? 500
                );
            }

            return $this->success($result['data']);
        } catch (\Exception $e) {
            return $this->error(
                'Failed to check refund status: ' . $e->getMessage(),
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
            $result = $phonePeService->handleWebhook($payload);

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
