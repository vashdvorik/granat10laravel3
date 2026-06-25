<?php

namespace Tests\Feature;

use Tests\TestCase;

class RedisConfigurationTest extends TestCase
{
    public function test_redis_workloads_use_separate_connections(): void
    {
        $this->assertSame('predis', config('database.redis.client'));
        $this->assertSame('1', config('database.redis.cache.database'));
        $this->assertSame('2', config('database.redis.session.database'));
        $this->assertSame('3', config('database.redis.queue.database'));
        $this->assertSame('queue', config('queue.connections.redis.connection'));
    }

    public function test_redis_queue_is_transaction_safe_and_blocking(): void
    {
        $this->assertTrue(config('queue.connections.redis.after_commit'));
        $this->assertSame(120, config('queue.connections.redis.retry_after'));
        $this->assertSame(5, config('queue.connections.redis.block_for'));
    }

    public function test_redis_session_uses_dedicated_connection_when_enabled(): void
    {
        config()->set('session.driver', 'redis');
        config()->set('session.connection', 'session');

        $this->assertSame('session', config('session.connection'));
    }
}
