<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;

final class OrderStatusNotification extends BaseFcmNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Order $order,
        protected string $status,
        protected ?string $orderImage = null
    ) {
        $this->title = 'Order Status Updated';
        $this->body = $this->getStatusMessage();
        $this->image = $orderImage;
        $this->path = '/orders/' . $order->id;
        $this->emailSubject = 'Your Order #' . $order->id . ' Status Update';

        $this->data = [
            'order_id' => (string) $order->id,
            'status' => $status,
            'type' => 'order_update',
            'timestamp' => now()->toISOString(),
        ];

        // Android specific config
        $this->androidConfig = [
            'sound' => 'default',
            'channel_id' => 'order_updates',
            'priority' => 'high',
        ];

        // iOS specific config
        $this->iosConfig = [
            'sound' => 'default',
            'badge' => 1,
            'category' => 'ORDER_UPDATE',
        ];

        // Web specific config
        $this->webConfig = [
            'icon' => url('/images/order-icon.png'),
        ];
    }

    /**
     * Get the status message based on order status.
     */
    private function getStatusMessage(): string
    {
        return match ($this->status) {
            'pending' => 'Your order #' . $this->order->id . ' has been received and is being processed.',
            'confirmed' => 'Great news! Your order #' . $this->order->id . ' has been confirmed.',
            'processing' => 'Your order #' . $this->order->id . ' is being prepared for shipment.',
            'shipped' => 'Your order #' . $this->order->id . ' has been shipped and is on its way!',
            'delivered' => 'Your order #' . $this->order->id . ' has been delivered. Enjoy!',
            'cancelled' => 'Your order #' . $this->order->id . ' has been cancelled.',
            'refunded' => 'Your order #' . $this->order->id . ' has been refunded.',
            default => 'Your order #' . $this->order->id . ' status has been updated to: ' . $this->status,
        };
    }
}
