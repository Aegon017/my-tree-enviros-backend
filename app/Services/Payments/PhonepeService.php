<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\CheckoutSession;
use PhonePe\payments\v2\standardCheckout\StandardCheckoutClient;
use PhonePe\payments\v2\models\request\builders\StandardCheckoutPayRequestBuilder;
use PhonePe\payments\v2\models\request\builders\StandardCheckoutRefundRequestBuilder;
use PhonePe\Env;
use RuntimeException;

final readonly class PhonepeService
{
    private StandardCheckoutClient $client;

    public function __construct()
    {
        $clientId = (string) config('services.phonepe.client_id');
        $clientSecret = (string) config('services.phonepe.client_secret');
        $clientVersion = (int) (config('services.phonepe.client_version') ?? 1);
        $env = config('services.phonepe.env', 'UAT') === 'PROD' ? Env::PRODUCTION : Env::UAT;

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('PhonePe credentials (client_id or client_secret) are missing in config.');
        }

        $this->client = StandardCheckoutClient::getInstance(
            $clientId,
            $clientVersion,
            $clientSecret,
            $env
        );
    }

    public function createGatewayOrder(CheckoutSession $session): array
    {
        $merchantOrderId = 'SESSION-' . $session->id . '-' . time();
        $amountInPaise = (int) round($session->pricing['grand_total'] * 100);

        $frontendUrl = config('app.frontend_url');
        $redirectUrl = $frontendUrl . '/payment/processing?gateway=phonepe&session=' . $session->session_token;

        try {
            $payRequest = StandardCheckoutPayRequestBuilder::builder()
                ->merchantOrderId($merchantOrderId)
                ->amount($amountInPaise)
                ->redirectUrl($redirectUrl)
                ->message('Order payment for session ' . $session->id)
                ->build();

            $payResponse = $this->client->pay($payRequest);

            if ($payResponse->getState() !== 'PENDING') {
                throw new RuntimeException('Payment initiation failed: ' . $payResponse->getState());
            }

            return [
                'gateway' => 'phonepe',
                'order_id' => $merchantOrderId,
                'phonepe_order_id' => $payResponse->getOrderId(),
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'url' => $payResponse->getRedirectUrl(),
                'state' => $payResponse->getState(),
                'expires_at' => $payResponse->getExpireAt(),
            ];
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            throw new RuntimeException('PhonePe SDK Error: ' . $e->getMessage(), 0, $e);
        }
    }

    public function verifyOrderStatus(string $merchantOrderId): array
    {
        try {
            $statusResponse = $this->client->getOrderStatus($merchantOrderId, true);

            return [
                'success' => true,
                'merchant_order_id' => $merchantOrderId,
                'phonepe_order_id' => $statusResponse->getOrderId(),
                'state' => $statusResponse->getState(),
                'amount' => $statusResponse->getAmount(),
                'payment_details' => $statusResponse->getPaymentDetails(),
                'error_code' => $statusResponse->getErrorCode(),
            ];
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function initiateRefund(string $originalMerchantOrderId, int $amountInPaise, string $merchantRefundId): array
    {
        try {
            $refundRequest = StandardCheckoutRefundRequestBuilder::builder()
                ->merchantRefundId($merchantRefundId)
                ->originalMerchantOrderId($originalMerchantOrderId)
                ->amount($amountInPaise)
                ->build();

            $refundResponse = $this->client->refund($refundRequest);

            return [
                'success' => true,
                'refund_id' => $refundResponse->getRefundId(),
                'state' => $refundResponse->getState(),
                'amount' => $refundResponse->getAmount(),
            ];
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function checkRefundStatus(string $merchantRefundId): array
    {
        try {
            $refundStatusResponse = $this->client->getRefundStatus($merchantRefundId);

            return [
                'success' => true,
                'refund_id' => $refundStatusResponse->getRefundId(),
                'state' => $refundStatusResponse->getState(),
                'amount' => $refundStatusResponse->getAmount(),
                'original_order_id' => $refundStatusResponse->getOriginalMerchantOrderId(),
                'payment_details' => $refundStatusResponse->getPaymentDetails(),
            ];
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
