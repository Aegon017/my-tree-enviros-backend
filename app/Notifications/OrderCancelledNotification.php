<?php

namespace App\Notifications;



use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;

class OrderCancelledNotification extends BaseAppNotification
{
    public function __construct(public Order $order)
    {
        $this->order->load('user');

        parent::__construct(
            title: 'Order Cancelled - #' . $this->order->reference_number,
            body: 'Order #' . $this->order->reference_number . ' has been cancelled. Reason: ' . $this->order->cancellation_reason,
            data: [
                'order_id' => $this->order->id,
                'reference_number' => $this->order->reference_number,
                'type' => 'order_cancelled'
            ],
            channels: ['mail', 'database', 'fcm']
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isCustomer = $notifiable instanceof \App\Models\User && $notifiable->id === $this->order->user_id;

        if ($isCustomer) {
            return (new MailMessage)
                ->subject('Order Cancelled - #' . $this->order->reference_number)
                ->greeting('Hello, ' . $notifiable->name)
                ->line('Your order #' . $this->order->reference_number . ' has been successfully cancelled.')
                ->line('Cancellation Reason: ' . $this->order->cancellation_reason)
                ->line('Refund Status: If your order was prepaid, the refund process has been initiated and will be credited to your original payment method within 5-7 business days.')
                ->action('View Order', url('/my-orders'))
                ->line('If you did not request this cancellation, please contact support immediately.');
        }

        // Admin Notification
        return (new MailMessage)
            ->subject('Order Cancelled Alert - #' . $this->order->reference_number)
            ->greeting('Admin Alert')
            ->line('An order has been cancelled.')
            ->line('Order #: ' . $this->order->reference_number)
            ->line('Customer: ' . $this->order->user->name . ' (' . $this->order->user->email . ')')
            ->line('Reason: ' . $this->order->cancellation_reason)
            ->line('Total Amount: ' . $this->order->grand_total)
            ->line('Please ensure any necessary refund actions are taken.')
            ->action('View Order in Admin', url('/admin/orders/' . $this->order->id));
    }
}
