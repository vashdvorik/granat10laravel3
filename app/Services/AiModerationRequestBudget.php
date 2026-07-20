<?php

namespace App\Services;

use App\Exceptions\AiModerationBudgetExceededException;
use Illuminate\Support\Facades\Cache;

class AiModerationRequestBudget
{
    public function consume(?string $scope = null): void
    {
        $provider = $scope ?? 'text:'.(string) config('moderation.provider', 'unknown');
        $hourlyLimit = max(0, (int) config('moderation.budget.hourly_requests', 60));
        $dailyLimit = max(0, (int) config('moderation.budget.daily_requests', 500));

        $this->consumePeriod(
            key: "moderation:budget:{$provider}:hour:".now()->format('YmdH'),
            limit: $hourlyLimit,
            expiresAt: now()->addHours(2),
            period: 'hourly',
        );

        try {
            $this->consumePeriod(
                key: "moderation:budget:{$provider}:day:".now()->format('Ymd'),
                limit: $dailyLimit,
                expiresAt: now()->addDays(2),
                period: 'daily',
            );
        } catch (AiModerationBudgetExceededException $exception) {
            $this->decrementCurrentHour($provider);

            throw $exception;
        }
    }

    /**
     * @return array{hourly_used: int, hourly_limit: int, daily_used: int, daily_limit: int}
     */
    public function usage(?string $scope = null): array
    {
        $provider = $scope ?? 'text:'.(string) config('moderation.provider', 'unknown');

        return [
            'hourly_used' => (int) Cache::get(
                "moderation:budget:{$provider}:hour:".now()->format('YmdH'),
                0
            ),
            'hourly_limit' => max(0, (int) config('moderation.budget.hourly_requests', 60)),
            'daily_used' => (int) Cache::get(
                "moderation:budget:{$provider}:day:".now()->format('Ymd'),
                0
            ),
            'daily_limit' => max(0, (int) config('moderation.budget.daily_requests', 500)),
        ];
    }

    private function consumePeriod(
        string $key,
        int $limit,
        \DateTimeInterface $expiresAt,
        string $period,
    ): void {
        if ($limit === 0) {
            return;
        }

        Cache::add($key, 0, $expiresAt);
        $used = Cache::increment($key);

        if ($used > $limit) {
            Cache::decrement($key);

            throw new AiModerationBudgetExceededException($period);
        }
    }

    private function decrementCurrentHour(string $provider): void
    {
        $key = "moderation:budget:{$provider}:hour:".now()->format('YmdH');

        if ((int) Cache::get($key, 0) > 0) {
            Cache::decrement($key);
        }
    }
}
