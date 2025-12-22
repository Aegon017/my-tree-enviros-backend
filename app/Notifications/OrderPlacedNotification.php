<?php

namespace App\Notifications;


use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;

class OrderPlacedNotification extends BaseAppNotification
{
    public function __construct(public Order $order)
    {
        $this->order->load('user');

        parent::__construct(
            title: 'Order Placed - #' . $this->order->reference_number,
            body: 'Your order has been placed successfully. You will receive updates as we process it.',
            data: [
                'order_id' => $this->order->id,
                'reference_number' => $this->order->reference_number,
                'type' => 'order_placed'
            ],
            channels: ['mail', 'database', 'fcm']
        );
    }


    public function toMail(object $notifiable): MailMessage
    {
        $isCustomer = $notifiable instanceof \App\Models\User && $notifiable->id === $this->order->user_id;

        if ($isCustomer) {
            return (new MailMessage)
                ->subject('Order Confirmation - #' . $this->order->reference_number)
                ->greeting('Hello ' . $notifiable->name . ',')
                ->line('Thank you for your order! We have received your order #' . $this->order->reference_number . ' and it is now being processed.')
                ->line('Amount: ' . $this->order->grand_total)
                ->line('Track your order status using the link below:')
                ->action('View Order', url('/my-orders'))
                ->line('We appreciate your business!');
        }

        return (new MailMessage)
            ->subject('New Order Placed - #' . $this->order->reference_number)
            ->greeting('Admin Alert')
            ->line('A new order has been placed.')
            ->line('Order #: ' . $this->order->reference_number)
            ->line('Customer: ' . $this->order->user->name . ' (' . $this->order->user->email . ')')
            ->line('Amount: ' . $this->order->grand_total)
            ->action('View Order in Admin', url('/admin/orders/' . $this->order->id));
    }
}
