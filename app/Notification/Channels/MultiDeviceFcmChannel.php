<?php

namespace App\Notification\Channels;

use App\Models\NotificationDeviceToken;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;

class MultiDeviceFcmChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toFcmMessage')) return;

        $tokens = NotificationDeviceToken::where('user_id', $notifiable->id)->pluck('token')->all();
        if (!$tokens) return;

        $message = $notification->toFcmMessage($notifiable);

        foreach ($tokens as $token) {
            app(FcmChannel::class)->sendToFcm($token, $message);
        }
    }
}
