<?php

namespace App\Contracts;

interface TelegramBotClient
{
    public function sendMessage(string $chatId, string $text): void;
}
