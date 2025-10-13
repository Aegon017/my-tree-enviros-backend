<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsLoginChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\OneTimePasswords\Notifications\OneTimePasswordNotification as NotificationsOneTimePasswordNotification;

class OneTimePasswordNotification extends NotificationsOneTimePasswordNotification implements ShouldQueue
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
            'message' => "Your OTP to sign in to My Tree is {$this->oneTimePassword->password} . Please do not share it with anyone.",
            'templateid' => config('services.sms_login.otptemplateid'),
        ];
    }
}
