<?php

declare(strict_types=1);

namespace App\Notifications;

final class GeneralNotification extends BaseFcmNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        ?string $title = null,
        ?string $body = null,
        ?string $image = null,
        ?string $path = null,
        array $data = [],
        ?string $emailSubject = null,
        array $androidConfig = [],
        array $iosConfig = [],
        array $webConfig = []
    ) {
        $this->title = $title;
        $this->body = $body;
        $this->image = $image;
        $this->path = $path;
        $this->emailSubject = $emailSubject ?? $title;

        $this->data = array_merge([
            'type' => 'general',
            'timestamp' => now()->toISOString(),
        ], $data);

        // Android specific config
        $this->androidConfig = array_merge([
            'sound' => 'default',
            'channel_id' => 'general_notifications',
            'priority' => 'default',
        ], $androidConfig);

        // iOS specific config
        $this->iosConfig = array_merge([
            'sound' => 'default',
            'badge' => 1,
            'category' => 'GENERAL',
        ], $iosConfig);

        // Web specific config
        $this->webConfig = array_merge([
            'icon' => url('/images/notification-icon.png'),
        ], $webConfig);
    }

    /**
     * Create a notification with title and message only.
     */
    public static function titleAndMessage(string $title, string $message, ?string $path = null): self
    {
        return new self(
            title: $title,
            body: $message,
            path: $path
        );
    }

    /**
     * Create a notification with title, message, and image.
     */
    public static function withImage(string $title, string $message, string $image, ?string $path = null): self
    {
        return new self(
            title: $title,
            body: $message,
            image: $image,
            path: $path
        );
    }

    /**
     * Create a notification with title and image only.
     */
    public static function titleAndImage(string $title, string $image, ?string $path = null): self
    {
        return new self(
            title: $title,
            image: $image,
            path: $path
        );
    }

    /**
     * Create a notification with image only.
     */
    public static function imageOnly(string $image, ?string $path = null): self
    {
        return new self(
            image: $image,
            path: $path
        );
    }
}
