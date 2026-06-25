<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification
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
            'type' => 'order_status_changed',
            'title' => 'Статус заказа изменён',
            'subject' => $this->order->number,
            'status_label' => $this->order->status->label(),
            'order_id' => $this->order->id,
            'url' => route('account.store.orders.show', $this->order),
        ];
    }
}
