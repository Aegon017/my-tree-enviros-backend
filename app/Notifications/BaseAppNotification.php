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
                $via[] = \NotificationChannels\Fcm\FcmChannel::class;
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
            ->greeting('Hello! ' . $notifiable->name)
            ->subject($this->title)
            ->line(new \Illuminate\Support\HtmlString($this->body));
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        $image = null;
        if (preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $this->body, $image)) {
            $image = $image['src'];
        }

        $notification = ResourcesNotification::create()
            ->title($this->title)
            ->body(trim(strip_tags($this->body)));

        if ($image) {
            $notification->image($image);
        }

        return FcmMessage::create()
            ->notification($notification)
            ->data(collect($this->data)->map(fn($value) => (string) $value)->toArray());
    }
}
