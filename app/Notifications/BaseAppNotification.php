<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notification\Channels\MultiDeviceFcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as ResourcesNotification;

final class BaseAppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $title, public string $body, public array $data = [], public array $channels = ['database', 'mail', 'fcm']) {}

    public function via(object $notifiable): array
    {
        $via = [];

        foreach ($this->channels as $channel) {
            if ($channel === 'database') {
                $via[] = 'database';
            }

            if ($channel === 'mail') {
                $via[] = 'mail';
            }

            if ($channel === 'fcm') {
                $via[] = MultiDeviceFcmChannel::class;
            }
        }

        return $via;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body);
    }

    public function toFcmMessage(object $notifiable): FcmMessage
    {
        return FcmMessage::create()
            ->setNotification(
                ResourcesNotification::create()
                    ->setTitle($this->title)
                    ->setBody($this->body)
            )
            ->setData($this->data);
    }
}
