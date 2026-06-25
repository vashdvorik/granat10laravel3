<?php

namespace App\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

class GeminiRequestThrottle
{
    public function __construct(
        private readonly ?AiModerationRequestBudget $requestBudget = null,
    ) {}

    public function wait(): void
    {
        ($this->requestBudget ?? app(AiModerationRequestBudget::class))->consume();

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
        $lock = Cache::lock('moderation:gemini:rate-limit-lock', 120);

        try {
            $lock->block(120, function () use ($intervalSeconds): void {
                $lastRequestAt = (float) Cache::get('moderation:gemini:last-request-at', 0);
                $waitMicroseconds = (int) max(
                    0,
                    (($lastRequestAt + $intervalSeconds) - microtime(true)) * 1_000_000
                );

                if ($waitMicroseconds > 0) {
                    usleep($waitMicroseconds);
                }

                Cache::put('moderation:gemini:last-request-at', microtime(true), 300);
            });
        } catch (LockTimeoutException) {
            sleep((int) ceil($intervalSeconds));
        }
    }
}
