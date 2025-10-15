<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class SmsLoginChannel
{
    /**
     * Send the notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $smsData = $notification->toPhone($notifiable);

        if (! $this->isValidSmsData($smsData)) {
            return;
        }

        $this->sendSmsRequest($smsData);
    }

    /**
     * Validate SMS data structure and content.
     */
    private function isValidSmsData(mixed $smsData): bool
    {
        return is_array($smsData) &&
            ! empty($smsData['phone']) &&
            ! empty($smsData['message']);
    }

    /**
     * Send SMS via API.
     */
    private function sendSmsRequest(array $smsData): void
    {
        $params = $this->buildRequestParams($smsData);
        $url = config('services.sms_login.endpoint');
        $response = Http::timeout(10)->get($url, $params);
        if (! $response->successful()) {
            throw new RuntimeException('SMS provider returned status: '.$response->status());
        }
    }

    /**
     * Build API request parameters.
     */
    private function buildRequestParams(array $smsData): array
    {
        $params = [
            'username' => config('services.sms_login.username'),
            'apikey' => config('services.sms_login.apikey'),
            'mobile' => $smsData['phone'],
            'senderid' => config('services.sms_login.senderid'),
            'message' => $smsData['message'],
            'templateid' => $smsData['templateid'] ?? null,
        ];

        return array_filter($params, fn ($value): bool => $value !== null && $value !== '');
    }
}
