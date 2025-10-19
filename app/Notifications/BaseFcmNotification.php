<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseFcmNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The notification title.
     */
    protected ?string $title = null;

    /**
     * The notification body/message.
     */
    protected ?string $body = null;

    /**
     * The notification image URL.
     */
    protected ?string $image = null;

    /**
     * The path/route to navigate when notification is clicked.
     */
    protected ?string $path = null;

    /**
     * Additional data to send with the notification.
     */
    protected array $data = [];

    /**
     * Android specific configuration.
     */
    protected array $androidConfig = [];

    /**
     * iOS specific configuration.
     */
    protected array $iosConfig = [];

    /**
     * Web specific configuration.
     */
    protected array $webConfig = [];

    /**
     * Email subject line.
     */
    protected ?string $emailSubject = null;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [FcmChannel::class];

        // Add mail channel if user has email
        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        $fcmData = [];

        if ($this->title) {
            $fcmData['title'] = $this->title;
        }

        if ($this->body) {
            $fcmData['body'] = $this->body;
        }

        if ($this->image) {
            $fcmData['image'] = $this->image;
        }

        if ($this->path) {
            $fcmData['path'] = $this->path;
            $fcmData['click_action'] = $this->path;
        }

        if (! empty($this->data)) {
            $fcmData['data'] = $this->data;
        }

        if (! empty($this->androidConfig)) {
            $fcmData['android'] = array_merge([
                'sound' => config('firebase.fcm.default_sound', 'default'),
                'channel_id' => 'default',
            ], $this->androidConfig);
        } else {
            $fcmData['android'] = [
                'sound' => config('firebase.fcm.default_sound', 'default'),
                'channel_id' => 'default',
            ];
        }

        if (! empty($this->iosConfig)) {
            $fcmData['ios'] = array_merge([
                'sound' => config('firebase.fcm.default_sound', 'default'),
                'badge' => config('firebase.fcm.default_badge', 1),
            ], $this->iosConfig);
        } else {
            $fcmData['ios'] = [
                'sound' => config('firebase.fcm.default_sound', 'default'),
                'badge' => config('firebase.fcm.default_badge', 1),
            ];
        }

        if (! empty($this->webConfig)) {
            $fcmData['web'] = $this->webConfig;
        }

        return $fcmData;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject($this->emailSubject ?? $this->title ?? config('app.name') . ' Notification')
            ->greeting($this->title ? 'Hello!' : '');

        if ($this->title && $this->emailSubject !== $this->title) {
            $mailMessage->line($this->title);
        }

        if ($this->body) {
            $mailMessage->line($this->body);
        }

        if ($this->image) {
            $mailMessage->line('![Image](' . $this->image . ')');
        }

        if ($this->path) {
            $mailMessage->action('View Details', url($this->path));
        }

        $mailMessage->line('Thank you for using ' . config('app.name') . '!');

        return $mailMessage;
    }

    /**
     * Set the notification title.
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set the notification body.
     */
    public function setBody(?string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Set the notification image.
     */
    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    /**
     * Set the notification path.
     */
    public function setPath(?string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Set additional data.
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Set Android configuration.
     */
    public function setAndroidConfig(array $config): self
    {
        $this->androidConfig = $config;
        return $this;
    }

    /**
     * Set iOS configuration.
     */
    public function setIosConfig(array $config): self
    {
        $this->iosConfig = $config;
        return $this;
    }

    /**
     * Set Web configuration.
     */
    public function setWebConfig(array $config): self
    {
        $this->webConfig = $config;
        return $this;
    }

    /**
     * Set email subject.
     */
    public function setEmailSubject(?string $subject): self
    {
        $this->emailSubject = $subject;
        return $this;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'image' => $this->image,
            'path' => $this->path,
            'data' => $this->data,
        ];
    }
}
