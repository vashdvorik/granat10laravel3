<?php

namespace App\Services;

use App\Models\ProductModerationRun;

class ModerationOperationsStatus
{
    /**
     * @return array<string, mixed>
     */
    public function current(): array
    {
        $health = app(ApplicationHealthService::class)->readiness();
        $staleBefore = now()->subMinutes(10);

        return [
            'queue_ok' => (bool) data_get($health, 'checks.queue_worker.ok', false),
            'queue_mode' => data_get($health, 'checks.queue_worker.mode'),
            'queue_age_seconds' => data_get($health, 'checks.queue_worker.age_seconds'),
            'image_moderation_enabled' => (bool) config('moderation.images.enabled', false),
            'api_key_configured' => filled(config('moderation.openrouter.api_key')),
            'text_model' => config('moderation.openrouter.text_model'),
            'image_model' => config('moderation.openrouter.image_model'),
            'image_queue' => config('moderation.images.queue'),
            'failed_runs' => ProductModerationRun::query()
                ->where('status', 'failed')
                ->count(),
            'failed_text_runs' => ProductModerationRun::query()
                ->where('provider', 'not like', 'image_%')
                ->where('status', 'failed')
                ->count(),
            'stuck_runs' => ProductModerationRun::query()
                ->where('status', 'processing')
                ->where('started_at', '<', $staleBefore)
                ->count(),
            'stuck_text_runs' => ProductModerationRun::query()
                ->where('provider', 'not like', 'image_%')
                ->where('status', 'processing')
                ->where('started_at', '<', $staleBefore)
                ->count(),
            'failed_image_runs' => ProductModerationRun::query()
                ->where('provider', 'like', 'image_%')
                ->where('status', 'failed')
                ->count(),
            'stuck_image_runs' => ProductModerationRun::query()
                ->where('provider', 'like', 'image_%')
                ->where('status', 'processing')
                ->where('started_at', '<', $staleBefore)
                ->count(),
        ];
    }
}
