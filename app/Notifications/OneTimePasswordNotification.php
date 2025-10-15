<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\SmsLoginChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\OneTimePasswords\Notifications\OneTimePasswordNotification as NotificationsOneTimePasswordNotification;

final class OneTimePasswordNotification extends NotificationsOneTimePasswordNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [SmsLoginChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toPhone(object $notifiable): array
    {
        return [
            'phone' => $notifiable->phone,
            'message' => sprintf('Your OTP to sign in to My Tree is %s . Please do not share it with anyone.', $this->oneTimePassword->password),
            'templateid' => config('services.sms_login.otptemplateid'),
        ];
    }
}
