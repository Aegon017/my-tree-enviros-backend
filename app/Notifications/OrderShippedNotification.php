<?php

namespace App\Notifications;



use App\Models\Order;

use Illuminate\Notifications\Messages\MailMessage;

class OrderShippedNotification extends BaseAppNotification
{
    public function __construct(public Order $order)
    {
        parent::__construct(
            title: 'Order Shipped - #' . $this->order->reference_number,
            body: 'Your order #' . $this->order->reference_number . ' has been shipped via ' . $this->order->courier_name . '.',
            data: [
                'order_id' => $this->order->id,
                'reference_number' => $this->order->reference_number,
                'type' => 'order_shipped'
            ],
            channels: ['mail', 'database', 'fcm']
        );
    }


    public function toMail(object $notifiable): MailMessage
    {
        $isCustomer = $notifiable instanceof \App\Models\User && $notifiable->id === $this->order->user_id;

        if ($isCustomer) {
            return (new MailMessage)
                ->subject('Order Shipped - #' . $this->order->reference_number)
                ->greeting('Hello ' . $notifiable->name . ',')
                ->line('Great news! Your order has been shipped.')
                ->line('Courier: ' . $this->order->courier_name)
                ->line('Tracking ID: ' . $this->order->tracking_id)
                ->action('Track Order', url('/my-orders'))
                ->line('Thank you for shopping with us!');
        }

        return (new MailMessage)
            ->subject('Order Shipped - #' . $this->order->reference_number)
            ->greeting('Admin Update')
            ->line('Order #' . $this->order->reference_number . ' has been marked as shpped.')
            ->line('Courier: ' . $this->order->courier_name)
            ->line('Tracking ID: ' . $this->order->tracking_id)
            ->action('View Order in Admin', url('/admin/orders/' . $this->order->id));
    }
}
