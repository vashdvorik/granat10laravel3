<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;

class CustomerOrderListService
{
    public const COOKIE_NAME = 'customer_orders';
    private const SESSION_KEY = 'customer_order_public_ids';
    private const SESSION_ACCESS_PREFIX = 'order_access.';
    private const COOKIE_MINUTES = 60 * 24 * 180;
    private const MAX_ORDERS = 25;

    public function remember(Request $request, string $publicId, string $accessToken): void
    {
        $entries = $this->entries($request);
        unset($entries[$publicId]);

        $entries = [$publicId => $accessToken] + $entries;
        $entries = array_slice($entries, 0, self::MAX_ORDERS, true);

        $this->store($request, $entries);
    }

    public function count(Request $request): int
    {
        return count($this->entries($request));
    }

    public function authorizedOrders(Request $request, OrderTokenService $tokens): Collection
    {
        $entries = $this->entries($request);

        if ($entries === []) {
            return collect();
        }

        $orders = Order::query()
            ->whereIn('public_id', array_keys($entries))
            ->recent()
            ->get()
            ->filter(function (Order $order) use ($entries, $tokens): bool {
                $accessToken = $entries[$order->public_id] ?? null;

                return is_string($accessToken)
                    && hash_equals($order->access_token_hash, $tokens->accessTokenHash($accessToken));
            })
            ->values();

        $validEntries = $orders
            ->mapWithKeys(fn (Order $order): array => [$order->public_id => $entries[$order->public_id]])
            ->all();

        if (count($validEntries) !== count($entries)) {
            $this->store($request, $validEntries);
        }

        return $orders;
    }

    public function accessTokenFor(Request $request, string $publicId): ?string
    {
        return $this->entries($request)[$publicId] ?? null;
    }

    public function forget(Request $request, string $publicId): void
    {
        $entries = $this->entries($request);
        unset($entries[$publicId]);

        $request->session()->forget(self::SESSION_ACCESS_PREFIX.$publicId);
        $this->store($request, $entries);
    }

    public function clear(Request $request): void
    {
        foreach (array_keys($this->entries($request)) as $publicId) {
            $request->session()->forget(self::SESSION_ACCESS_PREFIX.$publicId);
        }

        $request->session()->forget(self::SESSION_KEY);
        Cookie::queue(Cookie::forget(self::COOKIE_NAME, config('session.path'), config('session.domain')));
    }

    private function entries(Request $request): array
    {
        $entries = $this->cookieEntries($request);

        // На страницах 404 вне группы web сессии нет: показываем только то, что хранится в cookie.
        if (! $request->hasSession()) {
            return $entries;
        }

        foreach ((array) $request->session()->get(self::SESSION_KEY, []) as $publicId) {
            $accessToken = $request->session()->get(self::SESSION_ACCESS_PREFIX.$publicId);

            if (is_string($publicId) && is_string($accessToken)) {
                $entries[$publicId] = $accessToken;
            }
        }

        foreach ($request->session()->all() as $key => $accessToken) {
            if (! str_starts_with((string) $key, self::SESSION_ACCESS_PREFIX) || ! is_string($accessToken)) {
                continue;
            }

            $publicId = substr((string) $key, strlen(self::SESSION_ACCESS_PREFIX));
            $entries[$publicId] = $accessToken;
        }

        return array_slice($this->sanitize($entries), 0, self::MAX_ORDERS, true);
    }

    private function cookieEntries(Request $request): array
    {
        $raw = $request->cookie(self::COOKIE_NAME);

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function store(Request $request, array $entries): void
    {
        $entries = array_slice($this->sanitize($entries), 0, self::MAX_ORDERS, true);

        $request->session()->put(self::SESSION_KEY, array_keys($entries));

        foreach ($entries as $publicId => $accessToken) {
            $request->session()->put(self::SESSION_ACCESS_PREFIX.$publicId, $accessToken);
        }

        Cookie::queue(cookie(
            self::COOKIE_NAME,
            json_encode($entries, JSON_THROW_ON_ERROR),
            self::COOKIE_MINUTES,
            config('session.path'),
            config('session.domain'),
            config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax')
        ));
    }

    private function sanitize(array $entries): array
    {
        $clean = [];

        foreach ($entries as $publicId => $accessToken) {
            if (! is_string($publicId) || ! is_string($accessToken)) {
                continue;
            }

            if (! preg_match('/^[A-Za-z0-9]{20,40}$/', $publicId)) {
                continue;
            }

            if (! preg_match('/^[a-f0-9]{64}$/', $accessToken)) {
                continue;
            }

            $clean[$publicId] = $accessToken;
        }

        return $clean;
    }
}
