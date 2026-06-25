<?php

namespace Tests\Unit;

use App\Services\EnvironmentSettingsService;
use RuntimeException;
use Tests\TestCase;

class EnvironmentSettingsServiceTest extends TestCase
{
    private string $environmentPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->environmentPath = tempnam(sys_get_temp_dir(), 'granat-env-');
        file_put_contents($this->environmentPath, implode(PHP_EOL, [
            'APP_KEY=secret-app-key',
            'GEMINI_API_KEY=existing-secret',
            'PRODUCT_MODERATION_AI_ENABLED=false',
            '',
        ]));
        config()->set('environment-settings.path', $this->environmentPath);
    }

    protected function tearDown(): void
    {
        @unlink($this->environmentPath);
        @unlink($this->environmentPath.'.tmp');

        parent::tearDown();
    }

    public function test_it_updates_only_allowed_values_and_preserves_other_variables(): void
    {
        $service = app(EnvironmentSettingsService::class);

        $service->update([
            'PRODUCT_MODERATION_AI_ENABLED' => true,
            'PRODUCT_MODERATION_AI_MODE' => 'shadow',
        ]);

        $contents = file_get_contents($this->environmentPath);

        $this->assertStringContainsString('APP_KEY=secret-app-key', $contents);
        $this->assertStringContainsString('GEMINI_API_KEY=existing-secret', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AI_ENABLED=true', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AI_MODE=shadow', $contents);
    }

    public function test_it_rejects_unapproved_environment_variable(): void
    {
        $this->expectException(RuntimeException::class);

        app(EnvironmentSettingsService::class)->update([
            'APP_KEY' => 'replaced',
        ]);
    }
}
