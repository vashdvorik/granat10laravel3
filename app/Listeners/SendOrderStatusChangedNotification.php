<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderStatusChangedNotification implements ShouldQueue
{
    public int $tries = 3;

    public int $timeout = 30;

    public function handle(OrderStatusChanged $event): void
    {
        $order = Order::query()
            ->with('seller.user')
            ->find($event->orderId);
        $owner = $order?->seller?->user;

        if ($owner === null || $owner->id === $event->actorId) {
            return;
        }

        $owner->notify(new OrderStatusChangedNotification($order));
    }

    public function viaQueue(): string
    {
        return (string) config('orders.notifications_queue', 'orders');
    }
}
