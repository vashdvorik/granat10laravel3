<?php

namespace Tests\Feature;

use App\Jobs\RecordQueueWorkerHeartbeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_liveness_endpoint_is_available(): void
    {
        $this->get('/health/live')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    public function test_readiness_is_healthy_with_sync_queue(): void
    {
        config()->set('queue.default', 'sync');

        $this->get('/health/ready')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.ok', true)
            ->assertJsonPath('checks.cache.ok', true)
            ->assertJsonPath('checks.queue_worker.ok', true)
            ->assertJsonPath('checks.queue_worker.mode', 'sync');
    }

    public function test_readiness_fails_without_database_queue_worker_heartbeat(): void
    {
        config()->set('queue.default', 'database');
        Cache::forget(config('health.worker_heartbeat_key'));

        $this->get('/health/ready')
            ->assertStatus(503)
            ->assertJsonPath('status', 'unavailable')
            ->assertJsonPath('checks.queue_worker.ok', false);
    }

    public function test_heartbeat_job_marks_database_queue_worker_as_healthy(): void
    {
        config()->set('queue.default', 'database');

        (new RecordQueueWorkerHeartbeat)->handle();

        $this->get('/health/ready')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.queue_worker.ok', true);
    }
}
