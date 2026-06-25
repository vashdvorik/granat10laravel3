<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ApplicationHealthService
{
    public function readiness(): array
    {
        $checks = [
            'database' => $this->databaseCheck(),
            'cache' => $this->cacheCheck(),
            'queue_worker' => $this->queueWorkerCheck(),
        ];

        return [
            'ok' => collect($checks)->every(fn (array $check): bool => $check['ok']),
            'checks' => $checks,
        ];
    }

    private function databaseCheck(): array
    {
        try {
            DB::select('select 1');

            return ['ok' => true];
        } catch (Throwable) {
            return ['ok' => false];
        }
    }

    private function cacheCheck(): array
    {
        try {
            $key = 'health:cache:'.bin2hex(random_bytes(8));
            Cache::put($key, 'ok', 10);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);

            return ['ok' => $ok];
        } catch (Throwable) {
            return ['ok' => false];
        }
    }

    private function queueWorkerCheck(): array
    {
        if (config('queue.default') === 'sync') {
            return ['ok' => true, 'mode' => 'sync'];
        }

        $timestamp = Cache::get((string) config('health.worker_heartbeat_key'));
        $age = is_numeric($timestamp) ? now()->timestamp - (int) $timestamp : null;
        $maxAge = (int) config('health.worker_heartbeat_max_age', 180);

        return [
            'ok' => $age !== null && $age >= 0 && $age <= $maxAge,
            'age_seconds' => $age,
        ];
    }
}
