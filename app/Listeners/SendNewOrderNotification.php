<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Order;
use App\Notifications\NewOrderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNewOrderNotification implements ShouldQueue
{
    public int $tries = 3;

    public int $timeout = 30;

    public function handle(OrderCreated $event): void
    {
        $order = Order::query()
            ->with('seller.user')
            ->find($event->orderId);

        $order?->seller?->user?->notify(new NewOrderNotification($order));
    }

    public function viaQueue(): string
    {
        return (string) config('orders.notifications_queue', 'orders');
    }
}
