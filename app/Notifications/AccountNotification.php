<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Универсальное уведомление в кабинет: баланс, VIP и прочие события аккаунта.
 * Ключи subject/title/url показывает колокольчик в шапке кабинета.
 */
class AccountNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $extra  дополнительные данные (для поиска дублей и т.п.)
     */
    public function __construct(
        private readonly string $type,
        private readonly string $subject,
        private readonly string $message,
        private readonly ?string $url = null,
        private readonly array $extra = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->extra + [
            'type' => $this->type,
            'subject' => $this->subject,
            'title' => $this->message,
            'url' => $this->url ?? route('account.index'),
        ];
    }
}
