<?php

namespace App\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

class GeminiRequestThrottle
{
    private ?string $scope = null;

    public function __construct(
        private readonly ?AiModerationRequestBudget $requestBudget = null,
    ) {}

    public function forScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function wait(): void
    {
        $scope = $this->scope;
        $this->scope = null;
        ($this->requestBudget ?? app(AiModerationRequestBudget::class))->consume($scope);

        $requestsPerMinute = (int) (
            config('moderation.google.requests_per_minute')
            ?? config('moderation.http.requests_per_minute', 5)
        );

        if ($requestsPerMinute < 1) {
            return;
        }

        $intervalSeconds = (60 / $requestsPerMinute)
            + (int) (
                config('moderation.google.rate_limit_safety_seconds')
                ?? config('moderation.http.rate_limit_safety_seconds', 1)
            );
        $lockScope = str_replace([':', '/'], '-', $scope ?? 'shared');
        $lock = Cache::lock("moderation:{$lockScope}:rate-limit-lock", 120);

        try {
            $lock->block(120, function () use ($intervalSeconds, $lockScope): void {
                $lastRequestKey = "moderation:{$lockScope}:last-request-at";
                $lastRequestAt = (float) Cache::get($lastRequestKey, 0);
                $waitMicroseconds = (int) max(
                    0,
                    (($lastRequestAt + $intervalSeconds) - microtime(true)) * 1_000_000
                );

                if ($waitMicroseconds > 0) {
                    usleep($waitMicroseconds);
                }

                Cache::put($lastRequestKey, microtime(true), 300);
            });
        } catch (LockTimeoutException) {
            sleep((int) ceil($intervalSeconds));
        }
    }
}
