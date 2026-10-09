<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ModerationSettings;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ModerationConnectionTester;
use App\Services\ModerationSecretStore;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Настройки ИИ-модерации: один сервис — OpenRouter, один ключ на текст и фото. */
class ModerationSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private string $environmentPath;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));

        $this->environmentPath = tempnam(sys_get_temp_dir(), 'bimm-env-');
        file_put_contents($this->environmentPath, implode(PHP_EOL, [
            'QUEUE_CONNECTION=sync',
            'PRODUCT_MODERATION_AI_ENABLED=false',
            'PRODUCT_MODERATION_AI_MODE=shadow',
            'PRODUCT_MODERATION_QUEUE=moderation',
            'PRODUCT_MODERATION_TIMEOUT=60',
            'PRODUCT_MODERATION_MAX_ATTEMPTS=3',
            'PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK=0.10',
            'PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES=',
            'OPENROUTER_API_KEY=existing-secret',
            'OPENROUTER_TEXT_MODEL=openai/gpt-4o-mini',
            'OPENROUTER_IMAGE_MODEL=google/gemini-2.5-flash',
            'AI_MODERATION_MAX_OUTPUT_TOKENS=2048',
            'AI_MODERATION_RETRY_TIMES=2',
            'AI_MODERATION_RETRY_SLEEP_MS=500',
            'AI_MODERATION_REQUESTS_PER_MINUTE=5',
            'AI_MODERATION_RATE_LIMIT_SAFETY_SECONDS=1',
            'AI_MODERATION_HOURLY_REQUEST_LIMIT=60',
            'AI_MODERATION_DAILY_REQUEST_LIMIT=500',
            'AI_IMAGE_MODERATION_ENABLED=false',
            'AI_IMAGE_MODERATION_QUEUE=moderation',
            'AI_IMAGE_MODERATION_TIMEOUT=60',
            'AI_IMAGE_MODERATION_MAX_ATTEMPTS=3',
            // строки прежних сервисов остаются в .env нетронутыми: страница их не читает и не пишет
            'GEMINI_API_KEY=legacy-gemini-secret',
            'AI_TEXT_DEEPSEEK_API_KEY=legacy-deepseek-secret',
            'PRODUCT_MODERATION_AI_PROVIDER=deepseek',
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

    private function environment(): string
    {
        return (string) file_get_contents($this->environmentPath);
    }

    public function test_administrator_can_open_settings_page(): void
    {
        $this->actingAs($this->createAdministrator())
            ->get('/admin/moderation-settings')
            ->assertOk()
            ->assertSee('Настройки ИИ-модерации')
            ->assertSee('Подключение к OpenRouter')
            ->assertSee('Модель для текста')
            ->assertDontSee('existing-secret')
            ->assertDontSee('legacy-gemini-secret');
    }

    public function test_page_has_no_vocabulary_of_the_removed_providers(): void
    {
        $this->actingAs($this->createAdministrator())
            ->get('/admin/moderation-settings')
            ->assertOk()
            ->assertDontSee('Протокол API')
            ->assertDontSee('Формат API')
            ->assertDontSee('Базовый URL')
            ->assertDontSee('Адрес сервиса')
            ->assertDontSee('Заголовок для ключа')
            ->assertDontSee('Заголовок авторизации')
            ->assertDontSee('Префикс ключа')
            ->assertDontSee('Native moderation')
            ->assertDontSee('OpenAI-compatible')
            ->assertDontSee('Google Gemini')
            ->assertDontSee('Путь запроса');
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
                'strategy' => 'auto',
                'strictness' => 'strict',
                'excluded_categories' => ['weapons', 'medicine'],
                'queue' => 'moderation',
                'timeout' => 90,
                'max_attempts' => 4,
                'text_model' => 'openai/gpt-4o-mini',
                'api_key' => '',
                'max_output_tokens' => 2048,
                'retry_times' => 2,
                'retry_sleep_ms' => 500,
                'requests_per_minute' => 5,
                'rate_limit_safety_seconds' => 1,
                'hourly_request_limit' => 40,
                'daily_request_limit' => 300,
                'image_moderation_enabled' => true,
                'image_model' => 'google/gemini-2.5-flash',
                'image_queue' => 'moderation',
                'image_timeout' => 80,
                'image_max_attempts' => 2,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Настройки сохранены');

        $contents = $this->environment();

        $this->assertStringContainsString('QUEUE_CONNECTION=database', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AI_ENABLED=true', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AI_MODE=auto', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK=0.05', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES=weapons,medicine', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_TIMEOUT=90', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_MAX_ATTEMPTS=4', $contents);
        $this->assertStringContainsString('OPENROUTER_TEXT_MODEL=openai/gpt-4o-mini', $contents);
        $this->assertStringContainsString('OPENROUTER_IMAGE_MODEL=google/gemini-2.5-flash', $contents);
        $this->assertStringContainsString('AI_MODERATION_HOURLY_REQUEST_LIMIT=40', $contents);
        $this->assertStringContainsString('AI_MODERATION_DAILY_REQUEST_LIMIT=300', $contents);
        $this->assertStringContainsString('AI_IMAGE_MODERATION_ENABLED=true', $contents);
        $this->assertStringContainsString('AI_IMAGE_MODERATION_TIMEOUT=80', $contents);
        $this->assertStringContainsString('AI_IMAGE_MODERATION_MAX_ATTEMPTS=2', $contents);
        // ключ из .env не тронут, новый ключ не вводился
        $this->assertStringContainsString('OPENROUTER_API_KEY=existing-secret', $contents);
        $this->assertFalse(app(ModerationSecretStore::class)->has());
        // строки прежних сервисов остались как были
        $this->assertStringContainsString('GEMINI_API_KEY=legacy-gemini-secret', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AI_PROVIDER=deepseek', $contents);
    }

    public function test_new_key_is_stored_encrypted_and_never_written_to_env(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm(['api_key' => '  sk-or-v1-brand-new  '])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('sk-or-v1-brand-new', app(ModerationSecretStore::class)->get());
        $this->assertStringNotContainsString('sk-or-v1-brand-new', $this->environment());
        $this->assertNotSame(
            'sk-or-v1-brand-new',
            SiteSetting::query()->where('key', 'moderation.secret.openrouter')->value('value'),
        );
        // после сохранения рабочий ключ — новый
        $this->assertSame('sk-or-v1-brand-new', config('moderation.openrouter.api_key'));
    }

    public function test_saved_key_is_not_put_back_into_the_form(): void
    {
        app(ModerationSecretStore::class)->put('sk-or-v1-stored');
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->assertSet('data.api_key', null)
            ->assertSet('apiKeyConfigured', true);

        $this->get('/admin/moderation-settings')->assertDontSee('sk-or-v1-stored');
    }

    public function test_ai_cannot_be_enabled_without_any_key(): void
    {
        file_put_contents($this->environmentPath, str_replace('OPENROUTER_API_KEY=existing-secret', 'OPENROUTER_API_KEY=', $this->environment()));
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm(['strategy' => 'assist'])
            ->call('save')
            ->assertHasFormErrors(['api_key']);

        $this->assertStringContainsString('PRODUCT_MODERATION_AI_ENABLED=false', $this->environment());
    }

    public function test_photo_check_alone_also_needs_a_key(): void
    {
        file_put_contents($this->environmentPath, str_replace('OPENROUTER_API_KEY=existing-secret', 'OPENROUTER_API_KEY=', $this->environment()));
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm(['image_moderation_enabled' => true])
            ->call('save')
            ->assertHasFormErrors(['api_key']);
    }

    public function test_everything_can_be_switched_off_without_a_key(): void
    {
        file_put_contents($this->environmentPath, str_replace('OPENROUTER_API_KEY=existing-secret', 'OPENROUTER_API_KEY=', $this->environment()));
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm(['strategy' => 'manual', 'image_moderation_enabled' => false])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    /** @return array<string, array{0: string}> */
    public static function badModels(): array
    {
        return ['no vendor' => ['gpt-4o-mini'], 'spaces' => ['openai/gpt 4o'], 'url' => ['https://openrouter.ai/openai/gpt-4o'], 'empty' => ['']];
    }

    /** @dataProvider badModels */
    public function test_model_must_look_like_an_openrouter_model_id(string $model): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm(['text_model' => $model])
            ->call('save')
            ->assertHasFormErrors(['text_model']);

        $this->assertStringContainsString('OPENROUTER_TEXT_MODEL=openai/gpt-4o-mini', $this->environment());
    }

    public function test_free_and_variant_models_are_accepted(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm([
                'text_model' => 'meta-llama/llama-3.1-8b-instruct:free',
                'image_moderation_enabled' => true,
                'image_model' => 'google/gemma-3-27b-it:free',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertStringContainsString('OPENROUTER_TEXT_MODEL=meta-llama/llama-3.1-8b-instruct:free', $this->environment());
        $this->assertStringContainsString('OPENROUTER_IMAGE_MODEL=google/gemma-3-27b-it:free', $this->environment());
    }

    public function test_photo_model_is_kept_while_photo_check_is_switched_off(): void
    {
        $this->actingAs($this->createAdministrator());

        // поле модели скрыто, пока проверка фото выключена, но значение не теряется
        Livewire::test(ModerationSettings::class)
            ->fillForm(['image_moderation_enabled' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertStringContainsString('OPENROUTER_IMAGE_MODEL=google/gemini-2.5-flash', $this->environment());
    }

    public function test_settings_are_loaded_from_env_with_defaults_for_missing_values(): void
    {
        file_put_contents($this->environmentPath, "QUEUE_CONNECTION=database\n");
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->assertSet('data.text_model', 'openai/gpt-4o-mini')
            ->assertSet('data.image_model', 'openai/gpt-4o-mini')
            ->assertSet('data.strategy', 'manual')
            ->assertSet('data.hourly_request_limit', 60)
            ->assertSet('data.daily_request_limit', 500);
    }

    public function test_administrator_can_test_text_connection(): void
    {
        $this->actingAs($this->createAdministrator());

        $this->mock(ModerationConnectionTester::class, function ($mock): void {
            $mock->shouldReceive('text')->once()->andReturn(['ok' => true, 'message' => 'Connection test passed.']);
        });

        Livewire::test(ModerationSettings::class)
            ->call('testTextConnection')
            ->assertNotified('Текстовая модерация: подключение успешно');
    }

    public function test_administrator_can_test_image_connection_and_sees_a_failure(): void
    {
        $this->actingAs($this->createAdministrator());

        $this->mock(ModerationConnectionTester::class, function ($mock): void {
            $mock->shouldReceive('images')->once()->andReturn(['ok' => false, 'message' => 'Модель не умеет читать изображения.']);
        });

        Livewire::test(ModerationSettings::class)
            ->call('testImageConnection')
            ->assertNotified('Модерация изображений: подключение не удалось');
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
