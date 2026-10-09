<?php

namespace App\Listeners;

use App\Contracts\TelegramBotClient;
use App\Events\OrderCreated;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderTelegramNotification implements ShouldQueue
{
    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        private readonly TelegramBotClient $telegram,
    ) {}

    /** Сообщение о новом заказе. О смене статуса в Telegram не пишем: продавец сам её и меняет. */
    public function handle(OrderCreated $event): void
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

        $this->telegram->sendMessage($seller->telegram_chat_id, $this->newOrderMessage($order));
    }

    public function viaQueue(): string
    {
        return (string) config('orders.notifications_queue', 'orders');
    }

    private function newOrderMessage(Order $order): string
    {
        return implode("\n", array_filter([
            '🆕 Новый заказ '.$order->number,
            'Статус: '.$order->status->label(),
            '',
            'Покупатель: '.$order->customer_name,
            'Телефон: '.$order->customer_phone,
            '',
            'Товар: '.$order->product_name,
            'Количество: '.$order->quantity,
            'Цена: '.$this->formatMoney((float) $order->unit_price),
            'Итого: '.$this->formatMoney((float) $order->unit_price * $order->quantity),
            '',
            'Получение: '.$this->fulfillmentMethodLabel($order->fulfillment_method),
            filled($order->settlement_name) ? 'Город: '.$order->settlement_name : null,
            $order->isDelivery() && filled($order->delivery_address)
                ? 'Адрес доставки: '.$order->delivery_address
                : null,
            filled($order->customer_comment) ? 'Комментарий: '.$order->customer_comment : null,
            '',
            'Открыть заказ: '.route('account.store.orders.show', $order),
        ], static fn ($line): bool => $line !== null));
    }

    private function fulfillmentMethodLabel(string $method): string
    {
        return match ($method) {
            'delivery' => 'Доставка',
            'pickup' => 'Самовывоз',
            default => $method,
        };
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' руб.';
    }
}
