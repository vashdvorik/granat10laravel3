<?php

namespace App\Services;

use App\Models\Seller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TelegramConnectionService
{
    public function createLink(Seller $seller): string
    {
        $code = Str::lower(Str::random(32));
        Cache::put($this->key($code), $seller->id, now()->addMinutes(15));
        $username = ltrim((string) config('services.telegram.bot_username'), '@');

        return "https://t.me/{$username}?start={$code}";
    }

    public function confirm(string $code, string $chatId): ?Seller
    {
        $sellerId = Cache::pull($this->key($code));

        if ($sellerId === null) {
            return null;
        }

        $seller = Seller::query()->find($sellerId);

        if ($seller === null) {
            return null;
        }

        $seller->forceFill([
            'telegram_chat_id' => $chatId,
            'telegram_verified_at' => now(),
            'telegram_notifications_enabled' => true,
        ])->save();

        return $seller->refresh();
    }

    public function disconnect(Seller $seller): Seller
    {
        $seller->forceFill([
            'telegram_chat_id' => null,
            'telegram_verified_at' => null,
            'telegram_notifications_enabled' => false,
        ])->save();

        return $seller->refresh();
    }

    private function key(string $code): string
    {
        return 'telegram:connect:'.hash('sha256', $code);
    }
}
