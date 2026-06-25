<?php

namespace App\Services;

use InvalidArgumentException;

class AiProviderUrlValidator
{
    public function validate(string $url): void
    {
        $parts = parse_url($url);

        if (
            $parts === false
            || ($parts['scheme'] ?? null) !== 'https'
            || ! isset($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])
        ) {
            throw new InvalidArgumentException('Укажите безопасный HTTPS URL без логина, пароля и фрагмента.');
        }

        $host = trim(strtolower(rtrim($parts['host'], '.')), '[]');

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            throw new InvalidArgumentException('Локальные и внутренние адреса запрещены.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $this->assertPublicIp($host);

            return;
        }

        foreach ($this->resolveIps($host) as $ip) {
            $this->assertPublicIp($ip);
        }
    }

    private function assertPublicIp(string $ip): void
    {
        if (filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false) {
            throw new InvalidArgumentException('Локальные, служебные и внутренние сетевые адреса запрещены.');
        }
    }

    /**
     * @return array<int, string>
     */
    private function resolveIps(string $host): array
    {
        $records = dns_get_record($host, DNS_A | DNS_AAAA);

        if ($records === false) {
            return [];
        }

        return collect($records)
            ->flatMap(fn (array $record): array => array_values(array_filter([
                $record['ip'] ?? null,
                $record['ipv6'] ?? null,
            ])))
            ->unique()
            ->values()
            ->all();
    }
}
