<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_created',
            'title' => 'Новый заказ',
            'subject' => $this->order->number,
            'status_label' => $this->order->status->label(),
            'comment' => $this->order->product_name.' × '.$this->order->quantity,
            'order_id' => $this->order->id,
            'url' => route('account.store.orders.show', $this->order),
        ];
    }
}
