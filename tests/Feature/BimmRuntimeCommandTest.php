<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BimmRuntimeCommandTest extends TestCase
{
    public function test_dry_run_prints_runtime_processes_without_starting_them(): void
    {
        config()->set('queue.default', 'database');
        config()->set('moderation.queue', 'moderation');
        config()->set('moderation.images.queue', 'moderation');
        config()->set('orders.notifications_queue', 'orders');
        config()->set('services.telegram.bot_token', 'test-token');
        config()->set('services.telegram.webhook_secret', 'test-secret');

        $exitCode = Artisan::call('bimm:work', [
            '--dry-run' => true,
            '--serve' => true,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('http:', $output);
        $this->assertStringContainsString('queue:moderation:', $output);
        $this->assertStringContainsString('--queue=moderation', $output);
        $this->assertStringContainsString('default', $output);
        $this->assertStringContainsString('queue:orders:', $output);
        $this->assertStringContainsString('--queue=orders', $output);
        $this->assertStringContainsString('scheduler:', $output);
        $this->assertStringContainsString('schedule:work', $output);
        $this->assertStringNotContainsString('vite', $output);
    }
}
