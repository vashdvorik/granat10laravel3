<?php

namespace App\Services;

use App\Contracts\TelegramBotClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramHttpBotClient implements TelegramBotClient
{
    public function sendMessage(string $chatId, string $text): void
    {
        $token = (string) config('services.telegram.bot_token');

        if ($token === '') {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        Http::asJson()
            ->timeout((int) config('services.telegram.timeout', 10))
            ->retry(2, 500, throw: false)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ])
            ->throw();
    }
}
