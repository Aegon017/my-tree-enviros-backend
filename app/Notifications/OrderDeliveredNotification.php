<?php

namespace App\Notifications;



use App\Models\Order;

use Illuminate\Notifications\Messages\MailMessage;

class OrderDeliveredNotification extends BaseAppNotification
{
    public function __construct(public Order $order)
    {
        parent::__construct(
            title: 'Order Delivered - #' . $this->order->reference_number,
            body: 'Your order #' . $this->order->reference_number . ' has been delivered.',
            data: [
                'order_id' => $this->order->id,
                'reference_number' => $this->order->reference_number,
                'type' => 'order_delivered'
            ],
            channels: ['mail', 'database', 'fcm']
        );
    }


    public function toMail(object $notifiable): MailMessage
    {
        $isCustomer = $notifiable instanceof \App\Models\User && $notifiable->id === $this->order->user_id;

        if ($isCustomer) {
            return (new MailMessage)
                ->subject('Order Delivered - #' . $this->order->reference_number)
                ->greeting('Hello ' . $notifiable->name . ',')
                ->line('Your order has been delivered successfully.')
                ->line('Date: ' . ($this->order->delivered_at?->format('F j, Y, g:i a') ?? now()->format('F j, Y')))
                ->action('View Order', url('/my-orders'))
                ->line('We hope you enjoy your purchase!');
        }

        return (new MailMessage)
            ->subject('Order Delivered - #' . $this->order->reference_number)
            ->greeting('Admin Update')
            ->line('Order #' . $this->order->reference_number . ' has been marked as delivered.')
            ->line('Date: ' . ($this->order->delivered_at?->format('F j, Y, g:i a') ?? now()->format('F j, Y')))
            ->action('View Order in Admin', url('/admin/orders/' . $this->order->id));
    }
}
