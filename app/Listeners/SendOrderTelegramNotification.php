<?php

namespace App\Listeners;

use App\Contracts\TelegramBotClient;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderTelegramNotification implements ShouldQueue
{
    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        private readonly TelegramBotClient $telegram,
    ) {}

    public function handle(OrderCreated|OrderStatusChanged $event): void
    {
        $order = Order::query()->with('seller')->find($event->orderId);
        $seller = $order?->seller;

        if (
            $order === null
            || $seller === null
            || ! $seller->telegram_notifications_enabled
            || blank($seller->telegram_chat_id)
            || $seller->telegram_verified_at === null
        ) {
            return;
        }

        $message = $event instanceof OrderCreated
            ? implode("\n", [
                'Новый заказ '.$order->number,
                'Товар: '.$order->product_name,
                'Количество: '.$order->quantity,
                'Сумма: '.number_format((float) $order->unit_price * $order->quantity, 2, ',', ' ').' RUP',
                route('account.store.orders.show', $order),
            ])
            : implode("\n", [
                'Статус заказа '.$order->number.' изменён.',
                'Новый статус: '.$order->status->label(),
                route('account.store.orders.show', $order),
            ]);

        $this->telegram->sendMessage($seller->telegram_chat_id, $message);
    }

    public function viaQueue(): string
    {
        return (string) config('orders.notifications_queue', 'orders');
    }
}
