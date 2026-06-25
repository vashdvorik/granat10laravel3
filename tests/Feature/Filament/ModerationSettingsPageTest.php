<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ModerationSettings;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModerationSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private string $environmentPath;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));

        $this->environmentPath = tempnam(sys_get_temp_dir(), 'granat-env-');
        file_put_contents($this->environmentPath, implode(PHP_EOL, [
            'QUEUE_CONNECTION=sync',
            'PRODUCT_MODERATION_AI_ENABLED=false',
            'PRODUCT_MODERATION_AI_PROVIDER=google',
            'PRODUCT_MODERATION_AI_MODE=shadow',
            'PRODUCT_MODERATION_QUEUE=moderation',
            'PRODUCT_MODERATION_TIMEOUT=60',
            'PRODUCT_MODERATION_MAX_ATTEMPTS=3',
            'PRODUCT_MODERATION_FAKE_DECISION=manual_review',
            'PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK=0.10',
            'PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES=',
            'GEMINI_API_KEY=existing-secret',
            'GEMINI_MODEL=gemini-3.5-flash',
            'GEMINI_BASE_URL=https://generativelanguage.googleapis.com',
            'GEMINI_MAX_OUTPUT_TOKENS=2048',
            'GEMINI_RETRY_TIMES=2',
            'GEMINI_RETRY_SLEEP_MS=500',
            'GEMINI_REQUESTS_PER_MINUTE=5',
            'GEMINI_RATE_LIMIT_SAFETY_SECONDS=1',
            'AI_MODERATION_HOURLY_REQUEST_LIMIT=60',
            'AI_MODERATION_DAILY_REQUEST_LIMIT=500',
            'AI_IMAGE_MODERATION_ENABLED=false',
            'AI_IMAGE_MODERATION_PROVIDER=google',
            'AI_IMAGE_MODERATION_QUEUE=moderation',
            'AI_IMAGE_MODERATION_TIMEOUT=60',
            'AI_IMAGE_MODERATION_MAX_ATTEMPTS=3',
            'AI_IMAGE_MODERATION_API_KEY=existing-image-secret',
            'AI_IMAGE_MODERATION_MODEL=gemini-2.5-flash',
            'AI_IMAGE_MODERATION_BASE_URL=https://generativelanguage.googleapis.com/v1beta',
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

    public function test_administrator_can_open_settings_page(): void
    {
        $this->actingAs($this->createAdministrator())
            ->get('/admin/moderation-settings')
            ->assertOk()
            ->assertSee('Настройки ИИ-модерации')
            ->assertDontSee('Провайдер проверки')
            ->assertDontSee('Тестовый провайдер')
            ->assertDontSee('existing-secret');
    }

    public function test_regular_user_cannot_open_settings_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/moderation-settings')
            ->assertForbidden();
    }

    public function test_administrator_can_save_settings_without_replacing_existing_api_key(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm([
                'queue_connection' => 'database',
                'ai_enabled' => true,
                'mode' => 'auto',
                'queue' => 'moderation',
                'timeout' => 90,
                'max_attempts' => 4,
                'max_risk' => 5,
                'excluded_categories' => 'weapons,medicine',
                'gemini_api_key' => '',
                'gemini_model' => 'gemini-3.5-flash',
                'gemini_base_url' => 'https://generativelanguage.googleapis.com',
                'max_output_tokens' => 2048,
                'retry_times' => 2,
                'retry_sleep_ms' => 500,
                'requests_per_minute' => 5,
                'rate_limit_safety_seconds' => 1,
                'hourly_request_limit' => 40,
                'daily_request_limit' => 300,
                'image_moderation_enabled' => true,
                'image_api_key' => '',
                'image_model' => 'gemini-2.5-flash',
                'image_base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'image_queue' => 'moderation',
                'image_timeout' => 80,
                'image_max_attempts' => 2,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Настройки сохранены');

        $contents = file_get_contents($this->environmentPath);

        $this->assertStringContainsString('QUEUE_CONNECTION=database', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AI_ENABLED=true', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AI_MODE=auto', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK=0.05', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AI_PROVIDER=google', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_FAKE_DECISION=manual_review', $contents);
        $this->assertStringContainsString(
            'PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES=weapons,medicine',
            $contents
        );
        $this->assertStringContainsString('GEMINI_API_KEY=existing-secret', $contents);
        $this->assertStringContainsString('AI_MODERATION_HOURLY_REQUEST_LIMIT=40', $contents);
        $this->assertStringContainsString('AI_MODERATION_DAILY_REQUEST_LIMIT=300', $contents);
        $this->assertStringContainsString('AI_IMAGE_MODERATION_ENABLED=true', $contents);
        $this->assertStringContainsString('AI_IMAGE_MODERATION_PROVIDER=google', $contents);
        $this->assertStringContainsString('AI_IMAGE_MODERATION_QUEUE=moderation', $contents);
        $this->assertStringContainsString('AI_IMAGE_MODERATION_TIMEOUT=80', $contents);
        $this->assertStringContainsString('AI_IMAGE_MODERATION_MAX_ATTEMPTS=2', $contents);
        $this->assertStringContainsString('AI_IMAGE_MODERATION_API_KEY=existing-image-secret', $contents);
        $this->assertStringContainsString('AI_IMAGE_MODERATION_MODEL=gemini-2.5-flash', $contents);
    }

    public function test_administrator_cannot_save_private_provider_url(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm([
                'base_url' => 'https://127.0.0.1/v1',
            ])
            ->call('save')
            ->assertHasFormErrors(['base_url']);

        $this->assertStringNotContainsString(
            'AI_MODERATION_BASE_URL=https://127.0.0.1/v1',
            file_get_contents($this->environmentPath)
        );
    }

    private function createAdministrator(): User
    {
        $role = Role::query()->create([
            'name' => 'admin-'.uniqid(),
            'display_name' => 'Администратор',
        ]);
        $permission = Permission::query()->create([
            'key' => 'browse_admin',
            'table_name' => null,
        ]);
        $role->permissions()->attach($permission);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
