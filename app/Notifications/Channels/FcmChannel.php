<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\FcmToken;
use Exception;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;

final class FcmChannel
{
    /**
     * Send the given notification.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        // Get the FCM data from the notification
        $fcmData = $notification->toFcm($notifiable);

        if (! $this->isValidFcmData($fcmData)) {
            Log::warning('Invalid FCM notification data', [
                'notification_class' => $notification::class,
                'notifiable_id' => $notifiable->id ?? null,
            ]);

            return;
        }

        // Get all FCM tokens for the user
        $tokens = $this->getFcmTokens($notifiable);

        if ($tokens->isEmpty()) {
            Log::info('No FCM tokens found for user', [
                'user_id' => $notifiable->id ?? null,
            ]);

            return;
        }

        // Send notification to each token
        foreach ($tokens as $fcmToken) {
            $this->sendToToken($fcmToken->token, $fcmData);
        }
    }

    /**
     * Get FCM tokens for the notifiable entity.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getFcmTokens(mixed $notifiable)
    {
        if (method_exists($notifiable, 'fcmTokens')) {
            return $notifiable->fcmTokens()->active()->get();
        }

        if (method_exists($notifiable, 'routeNotificationForFcm')) {
            $tokens = $notifiable->routeNotificationForFcm();

            return collect(is_array($tokens) ? $tokens : [$tokens]);
        }

        return collect();
    }

    /**
     * Send notification to a specific token.
     */
    private function sendToToken(string $token, array $fcmData): void
    {
        try {
            $messaging = Firebase::messaging();

            // Build the cloud message
            $message = $this->buildMessage($token, $fcmData);

            // Send the message
            $messaging->send($message);

            Log::info('FCM notification sent successfully', [
                'token' => mb_substr($token, 0, 20).'...',
                'title' => $fcmData['title'] ?? null,
            ]);

        } catch (MessagingException $e) {
            $this->handleMessagingException($e, $token);
        } catch (FirebaseException $e) {
            Log::error('Firebase exception while sending FCM notification', [
                'error' => $e->getMessage(),
                'token' => mb_substr($token, 0, 20).'...',
            ]);
        } catch (Exception $e) {
            Log::error('Exception while sending FCM notification', [
                'error' => $e->getMessage(),
                'token' => mb_substr($token, 0, 20).'...',
            ]);
        }
    }

    /**
     * Build the cloud message.
     */
    private function buildMessage(string $token, array $fcmData): CloudMessage
    {
        [
            'priority' => config('firebase.fcm.priority', 'high'),
            'time_to_live' => config('firebase.fcm.ttl', 3600),
        ];

        // Build notification payload
        $notificationData = [];

        if (! empty($fcmData['title'])) {
            $notificationData['title'] = $fcmData['title'];
        }

        if (! empty($fcmData['body'])) {
            $notificationData['body'] = $fcmData['body'];
        }

        if (! empty($fcmData['image'])) {
            $notificationData['image'] = $fcmData['image'];
        }

        // Start building the message
        $messageBuilder = CloudMessage::withTarget('token', $token);

        // Add notification if we have title or body
        if ($notificationData !== []) {
            $notification = FirebaseNotification::create(
                $notificationData['title'] ?? '',
                $notificationData['body'] ?? ''
            );

            if (! empty($notificationData['image'])) {
                $notification = $notification->withImage($notificationData['image']);
            }

            $messageBuilder = $messageBuilder->withNotification($notification);
        }

        // Add data payload
        $dataPayload = $fcmData['data'] ?? [];

        // Add click action / deep link
        if (! empty($fcmData['click_action'])) {
            $dataPayload['click_action'] = $fcmData['click_action'];
        }

        if (! empty($fcmData['path'])) {
            $dataPayload['path'] = $fcmData['path'];
        }

        // Add image to data if notification is image-only
        if (! empty($fcmData['image']) && empty($fcmData['title']) && empty($fcmData['body'])) {
            $dataPayload['image'] = $fcmData['image'];
            $dataPayload['type'] = 'image_only';
        }

        if (! empty($dataPayload)) {
            $messageBuilder = $messageBuilder->withData($dataPayload);
        }

        // Add Android specific config
        if (! empty($fcmData['android'])) {
            $androidConfig = $fcmData['android'];

            // Add sound
            if (! empty($androidConfig['sound'])) {
                $dataPayload['sound'] = $androidConfig['sound'];
            }

            // Add channel
            if (! empty($androidConfig['channel_id'])) {
                $dataPayload['android_channel_id'] = $androidConfig['channel_id'];
            }
        }

        // Add iOS specific config
        if (! empty($fcmData['ios'])) {
            $iosConfig = $fcmData['ios'];

            // Add badge
            if (isset($iosConfig['badge'])) {
                $dataPayload['badge'] = (string) $iosConfig['badge'];
            }

            // Add sound
            if (! empty($iosConfig['sound'])) {
                $dataPayload['sound'] = $iosConfig['sound'];
            }
        }

        // Add web specific config
        if (! empty($fcmData['web'])) {
            $webConfig = $fcmData['web'];

            if (! empty($webConfig['icon'])) {
                $dataPayload['icon'] = $webConfig['icon'];
            }
        }

        return $messageBuilder;
    }

    /**
     * Validate FCM data structure.
     */
    private function isValidFcmData(mixed $fcmData): bool
    {
        if (! is_array($fcmData)) {
            return false;
        }

        // At least one of these should be present
        return ! empty($fcmData['title']) ||
               ! empty($fcmData['body']) ||
               ! empty($fcmData['image']) ||
               ! empty($fcmData['data']);
    }

    /**
     * Handle messaging exceptions (e.g., invalid tokens).
     */
    private function handleMessagingException(MessagingException $e, string $token): void
    {
        $errorCode = method_exists($e, 'getCode') ? $e->getCode() : null;

        // Check if token is invalid or unregistered
        if ($this->isInvalidTokenError($e)) {
            Log::warning('Invalid or unregistered FCM token, removing from database', [
                'token' => mb_substr($token, 0, 20).'...',
                'error' => $e->getMessage(),
            ]);

            // Remove invalid token from database
            FcmToken::where('token', $token)->delete();
        } else {
            Log::error('Messaging exception while sending FCM notification', [
                'error' => $e->getMessage(),
                'code' => $errorCode,
                'token' => mb_substr($token, 0, 20).'...',
            ]);
        }
    }

    /**
     * Check if the exception indicates an invalid token.
     */
    private function isInvalidTokenError(MessagingException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'not a valid FCM registration token') ||
               str_contains($message, 'registration token is not a valid') ||
               str_contains($message, 'Requested entity was not found') ||
               str_contains($message, 'The registration token is not a valid');
    }
}
