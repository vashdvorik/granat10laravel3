<?php

namespace App\Services;

class OrderTokenService
{
    public function accessToken(string $idempotencyKey): string
    {
        return hash_hmac('sha256', 'order-access:'.$idempotencyKey, $this->key());
    }

    public function accessTokenHash(string $accessToken): string
    {
        return hash_hmac('sha256', $accessToken, $this->key());
    }

    public function idempotencyHash(string $idempotencyKey): string
    {
        return hash_hmac('sha256', 'order-idempotency:'.$idempotencyKey, $this->key());
    }

    public function phoneHash(string $phone): string
    {
        return hash_hmac('sha256', $this->normalizePhone($phone), $this->key());
    }

    public function sourceIpHash(?string $ip): ?string
    {
        return $ip === null || $ip === ''
            ? null
            : hash_hmac('sha256', 'order-ip:'.$ip, $this->key());
    }

    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', trim($phone)) ?? '';

        return str_starts_with($phone, '+')
            ? '+'.preg_replace('/\D/', '', mb_substr($phone, 1))
            : preg_replace('/\D/', '', $phone);
    }

    private function key(): string
    {
        return (string) config('app.key');
    }
}
