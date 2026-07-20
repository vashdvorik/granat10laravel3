<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ModerationSettings;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ModerationSecretStore;
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

    public function test_image_settings_do_not_inherit_text_provider_endpoint(): void
    {
        file_put_contents($this->environmentPath, implode(PHP_EOL, [
            'QUEUE_CONNECTION=sync',
            'PRODUCT_MODERATION_AI_ENABLED=true',
            'PRODUCT_MODERATION_AI_PROVIDER=openai_compatible',
            'PRODUCT_MODERATION_AI_MODE=auto',
            'PRODUCT_MODERATION_QUEUE=moderation',
            'PRODUCT_MODERATION_TIMEOUT=60',
            'PRODUCT_MODERATION_MAX_ATTEMPTS=3',
            'PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK=0.10',
            'PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES=',
            'AI_MODERATION_MODEL=deepseek-v4-flash',
            'AI_MODERATION_BASE_URL=https://api.deepseek.com',
            'AI_MODERATION_CHAT_COMPLETIONS_PATH=/chat/completions',
            'AI_MODERATION_MAX_OUTPUT_TOKENS=2000',
            'AI_MODERATION_RETRY_TIMES=2',
            'AI_MODERATION_RETRY_SLEEP_MS=1000',
            'AI_MODERATION_REQUESTS_PER_MINUTE=60',
            'AI_MODERATION_RATE_LIMIT_SAFETY_SECONDS=1',
            'AI_MODERATION_HOURLY_REQUEST_LIMIT=60',
            'AI_MODERATION_DAILY_REQUEST_LIMIT=500',
            'AI_IMAGE_MODERATION_ENABLED=false',
            'AI_IMAGE_MODERATION_QUEUE=moderation',
            'AI_IMAGE_MODERATION_TIMEOUT=60',
            'AI_IMAGE_MODERATION_MAX_ATTEMPTS=3',
            '',
        ]));

        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->assertSet('data.model', 'deepseek-v4-flash')
            ->assertSet('data.base_url', 'https://api.deepseek.com')
            ->assertSet('data.image_model', 'gemini-2.5-flash')
            ->assertSet('data.image_base_url', 'https://generativelanguage.googleapis.com/v1beta');
    }

    public function test_administrator_can_switch_image_moderation_to_native_openai(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm([
                'image_provider' => 'openai_moderation',
                'image_api_key' => 'openai-test-key',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $contents = file_get_contents($this->environmentPath);

        $this->assertStringContainsString('PRODUCT_MODERATION_AI_PROVIDER=google', $contents);
        $this->assertStringContainsString('AI_IMAGE_MODERATION_PROVIDER=openai_moderation', $contents);
        $this->assertStringContainsString('AI_IMAGE_MODERATION_MODEL=omni-moderation-latest', $contents);
        $this->assertStringContainsString('AI_IMAGE_MODERATION_BASE_URL=https://api.openai.com/v1', $contents);
        $this->assertSame('openai-test-key', app(ModerationSecretStore::class)->get('image.openai_moderation'));
        $this->assertStringNotContainsString('openai-test-key', $contents);
        $this->assertNotSame(
            'openai-test-key',
            SiteSetting::query()->where('key', 'moderation.secret.image.openai_moderation')->value('value')
        );
    }

    public function test_administrator_can_select_deepseek_as_text_provider(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->set('data.provider', 'deepseek')
            ->assertSet('data.model', 'deepseek-chat')
            ->assertSet('data.base_url', 'https://api.deepseek.com')
            ->fillForm(['api_key' => 'deepseek-test-key'])
            ->call('save')
            ->assertHasNoFormErrors();

        $contents = file_get_contents($this->environmentPath);
        $this->assertStringContainsString('PRODUCT_MODERATION_AI_PROVIDER=deepseek', $contents);
        $this->assertStringContainsString('AI_TEXT_DEEPSEEK_MODEL=deepseek-chat', $contents);
        $this->assertStringContainsString('AI_TEXT_DEEPSEEK_BASE_URL=https://api.deepseek.com', $contents);
        $this->assertSame('deepseek-test-key', app(ModerationSecretStore::class)->get('text.deepseek'));
        $this->assertStringNotContainsString('deepseek-test-key', $contents);
    }

    public function test_google_provider_rejects_deepseek_model(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm([
                'provider' => 'google',
                'model' => 'deepseek-chat',
                'base_url' => 'https://api.deepseek.com',
            ])
            ->call('save')
            ->assertHasFormErrors(['provider']);
    }

    public function test_provider_specific_image_keys_are_preserved_when_switching_provider(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm([
                'image_provider' => 'google',
                'image_api_key' => 'google-image-key',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(ModerationSettings::class)
            ->fillForm([
                'image_provider' => 'openai_moderation',
                'image_api_key' => 'openai-image-key',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $store = app(ModerationSecretStore::class);
        $this->assertSame('google-image-key', $store->get('image.google'));
        $this->assertSame('openai-image-key', $store->get('image.openai_moderation'));
        $this->assertStringNotContainsString('google-image-key', file_get_contents($this->environmentPath));
        $this->assertStringNotContainsString('openai-image-key', file_get_contents($this->environmentPath));
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
