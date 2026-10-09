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
            'OPENROUTER_API_KEY=existing-secret',
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
        $this->assertStringContainsString('OPENROUTER_API_KEY=existing-secret', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AI_ENABLED=true', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AI_MODE=shadow', $contents);
    }

    public function test_settings_of_removed_ai_providers_can_no_longer_be_written(): void
    {
        foreach (['GEMINI_API_KEY', 'AI_TEXT_DEEPSEEK_API_KEY', 'AI_IMAGE_MODERATION_PROVIDER', 'PRODUCT_MODERATION_AI_PROVIDER'] as $key) {
            try {
                app(EnvironmentSettingsService::class)->update([$key => 'value']);
                $this->fail("Переменная {$key} не должна быть доступна для изменения.");
            } catch (RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertStringNotContainsString('value', (string) file_get_contents($this->environmentPath));
    }

    public function test_openrouter_settings_can_be_written(): void
    {
        app(EnvironmentSettingsService::class)->update([
            'OPENROUTER_TEXT_MODEL' => 'meta-llama/llama-3.1-8b-instruct:free',
            'OPENROUTER_IMAGE_MODEL' => 'openai/gpt-4o-mini',
        ]);

        $contents = (string) file_get_contents($this->environmentPath);

        $this->assertStringContainsString('OPENROUTER_TEXT_MODEL=meta-llama/llama-3.1-8b-instruct:free', $contents);
        $this->assertStringContainsString('OPENROUTER_IMAGE_MODEL=openai/gpt-4o-mini', $contents);
    }

    public function test_it_rejects_unapproved_environment_variable(): void
    {
        $this->expectException(RuntimeException::class);

        app(EnvironmentSettingsService::class)->update([
            'APP_KEY' => 'replaced',
        ]);
    }
}
