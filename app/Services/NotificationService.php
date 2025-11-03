<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Notifications\GeneralNotification;
use App\Notifications\ImageOnlyNotification;
use App\Notifications\OrderStatusNotification;
use App\Notifications\ProductPromotionNotification;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final class NotificationService
{
    /**
     * Send a general notification to a user.
     */
    public function sendGeneralNotification(
        User $user,
        ?string $title = null,
        ?string $body = null,
        ?string $image = null,
        ?string $path = null,
        array $data = [],
        array $androidConfig = [],
        array $iosConfig = [],
        array $webConfig = []
    ): void {
        try {
            $notification = new GeneralNotification(
                title: $title,
                body: $body,
                image: $image,
                path: $path,
                data: $data,
                androidConfig: $androidConfig,
                iosConfig: $iosConfig,
                webConfig: $webConfig
            );

            $user->notify($notification);

            Log::info('General notification sent', [
                'user_id' => $user->id,
                'title' => $title,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send general notification', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send notification with title and message only.
     */
    public function sendTitleAndMessage(
        User $user,
        string $title,
        string $message,
        ?string $path = null
    ): void {
        try {
            $notification = GeneralNotification::titleAndMessage($title, $message, $path);
            $user->notify($notification);

            Log::info('Title and message notification sent', [
                'user_id' => $user->id,
                'title' => $title,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send title and message notification', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send notification with title, message, and image.
     */
    public function sendWithImage(
        User $user,
        string $title,
        string $message,
        string $image,
        ?string $path = null
    ): void {
        try {
            $notification = GeneralNotification::withImage($title, $message, $image, $path);
            $user->notify($notification);

            Log::info('Notification with image sent', [
                'user_id' => $user->id,
                'title' => $title,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send notification with image', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send notification with title and image only.
     */
    public function sendTitleAndImage(
        User $user,
        string $title,
        string $image,
        ?string $path = null
    ): void {
        try {
            $notification = GeneralNotification::titleAndImage($title, $image, $path);
            $user->notify($notification);

            Log::info('Title and image notification sent', [
                'user_id' => $user->id,
                'title' => $title,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send title and image notification', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send image-only notification.
     */
    public function sendImageOnly(
        User $user,
        string $image,
        ?string $path = null,
        array $additionalData = []
    ): void {
        try {
            $notification = new ImageOnlyNotification($image, $path ?? '/', $additionalData);
            $user->notify($notification);

            Log::info('Image-only notification sent', [
                'user_id' => $user->id,
                'image' => $image,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send image-only notification', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send order status notification.
     *
     * @param  \App\Models\Order  $order
     */
    public function sendOrderStatusNotification(
        User $user,
        $order,
        string $status,
        ?string $orderImage = null
    ): void {
        try {
            $notification = new OrderStatusNotification($order, $status, $orderImage);
            $user->notify($notification);

            Log::info('Order status notification sent', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'status' => $status,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send order status notification', [
                'user_id' => $user->id,
                'order_id' => $order->id ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send product promotion notification.
     *
     * @param  \App\Models\Product  $product
     */
    public function sendProductPromotionNotification(
        User $user,
        $product,
        string $promotionTitle,
        string $promotionImage
    ): void {
        try {
            $notification = new ProductPromotionNotification($product, $promotionTitle, $promotionImage);
            $user->notify($notification);

            Log::info('Product promotion notification sent', [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send product promotion notification', [
                'user_id' => $user->id,
                'product_id' => $product->id ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to multiple users.
     */
    public function sendToMultipleUsers(
        array $userIds,
        ?string $title = null,
        ?string $body = null,
        ?string $image = null,
        ?string $path = null,
        array $data = []
    ): void {
        try {
            $users = User::whereIn('id', $userIds)->get();

            $notification = new GeneralNotification(
                title: $title,
                body: $body,
                image: $image,
                path: $path,
                data: $data
            );

            Notification::send($users, $notification);

            Log::info('Notification sent to multiple users', [
                'user_count' => $users->count(),
                'title' => $title,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send notification to multiple users', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to all users.
     */
    public function sendToAllUsers(
        ?string $title = null,
        ?string $body = null,
        ?string $image = null,
        ?string $path = null,
        array $data = []
    ): void {
        try {
            $users = User::all();

            $notification = new GeneralNotification(
                title: $title,
                body: $body,
                image: $image,
                path: $path,
                data: $data
            );

            Notification::send($users, $notification);

            Log::info('Notification sent to all users', [
                'user_count' => $users->count(),
                'title' => $title,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send notification to all users', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send notification based on custom query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function sendToCustomQuery(
        $query,
        ?string $title = null,
        ?string $body = null,
        ?string $image = null,
        ?string $path = null,
        array $data = []
    ): void {
        try {
            // Process in chunks to avoid memory issues
            $query->chunk(100, function ($users) use ($title, $body, $image, $path, $data): void {
                $notification = new GeneralNotification(
                    title: $title,
                    body: $body,
                    image: $image,
                    path: $path,
                    data: $data
                );

                Notification::send($users, $notification);
            });

            Log::info('Notification sent to custom query users', [
                'title' => $title,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send notification to custom query users', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
