<?php

namespace App\Http\Controllers;

use App\Contracts\TelegramBotClient;
use App\Services\TelegramConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TelegramConnectionService $connections,
        TelegramBotClient $telegram,
    ): JsonResponse {
        $webhookSecret = (string) config('services.telegram.webhook_secret');

        abort_unless(
            $webhookSecret !== ''
            && hash_equals(
                $webhookSecret,
                (string) $request->header('X-Telegram-Bot-Api-Secret-Token'),
            ),
            403,
        );

        $text = (string) data_get($request->all(), 'message.text', '');
        $chatId = data_get($request->all(), 'message.chat.id');

        if (is_numeric($chatId) && preg_match('/^\/start\s+([a-z0-9]+)$/i', trim($text), $matches)) {
            $seller = $connections->confirm($matches[1], (string) $chatId);
            $telegram->sendMessage(
                (string) $chatId,
                $seller
                    ? 'Telegram успешно подключён к магазину «'.$seller->name.'».'
                    : 'Код подключения недействителен или истёк. Создайте новый код в кабинете.',
            );
        }

        return response()->json(['ok' => true]);
    }
}
