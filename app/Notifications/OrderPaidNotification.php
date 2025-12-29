<?php

namespace App\Notifications;


use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;

class OrderPaidNotification extends BaseAppNotification
{
    public function __construct(public Order $order)
    {
        $this->order->load('user');

        parent::__construct(
            title: 'Payment Received - #' . $this->order->reference_number,
            body: 'We have received payment for your order. We will start preparing it for shipment.',
            data: [
                'order_id' => $this->order->id,
                'reference_number' => $this->order->reference_number,
                'type' => 'order_paid'
            ],
            channels: ['mail', 'database', 'fcm']
        );
    }


    public function toMail(object $notifiable): MailMessage
    {
        $isCustomer = $notifiable instanceof \App\Models\User && $notifiable->id === $this->order->user_id;

        if ($isCustomer) {
            return (new MailMessage)
                ->subject('Payment Received - #' . $this->order->reference_number)
                ->greeting('Hello ' . $notifiable->name . ',')
                ->line('Your payment for order #' . $this->order->reference_number . ' has been successfully received.')
                ->line('We are now preparing your items for shipment.')
                ->action('View Order', url('/my-orders'))
                ->line('Thank you!');
        }

        return (new MailMessage)
            ->subject('Order Payment Received - #' . $this->order->reference_number)
            ->greeting('Admin Alert')
            ->line('Payment received for order #' . $this->order->reference_number)
            ->line('Customer: ' . $this->order->user->name . ' (' . $this->order->user->email . ')')
            ->line('Amount: ' . $this->order->grand_total)
            ->action('View Order in Admin', url('/admin/orders/' . $this->order->id));
    }
}
