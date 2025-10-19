<?php

declare(strict_types=1);

namespace App\Notifications;

final class ImageOnlyNotification extends BaseFcmNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected string $imageUrl,
        protected string $targetPath,
        protected array $additionalData = []
    ) {
        $this->title = null; // No title
        $this->body = null; // No body
        $this->image = $imageUrl;
        $this->path = $targetPath;
        $this->emailSubject = 'New Image Notification';

        $this->data = array_merge([
            'type' => 'image_only',
            'image_url' => $imageUrl,
            'timestamp' => now()->toISOString(),
        ], $additionalData);

        // Android specific config
        $this->androidConfig = [
            'sound' => 'default',
            'channel_id' => 'image_notifications',
            'priority' => 'default',
        ];

        // iOS specific config
        $this->iosConfig = [
            'sound' => 'default',
            'badge' => 1,
            'category' => 'IMAGE_NOTIFICATION',
        ];

        // Web specific config
        $this->webConfig = [
            'icon' => $imageUrl,
            'image' => $imageUrl,
        ];
    }

    /**
     * Override toMail to handle image-only notifications better.
     */
    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject($this->emailSubject ?? config('app.name') . ' Notification')
            ->greeting('Hello!')
            ->line('You have received a new image notification.')
            ->line('![Notification Image](' . $this->image . ')')
            ->action('View Details', url($this->path))
            ->line('Thank you for using ' . config('app.name') . '!');
    }
}
