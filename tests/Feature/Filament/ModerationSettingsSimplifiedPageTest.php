<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ModerationSettings;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Страница настроек ИИ-модерации в упрощённом виде: одно главное решение, понятные формулировки,
 * техника спрятана в «Дополнительные настройки», а скрытые поля не теряют сохранённых значений.
 */
class ModerationSettingsSimplifiedPageTest extends TestCase
{
    use RefreshDatabase;

    private string $environmentPath;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));

        $this->environmentPath = tempnam(sys_get_temp_dir(), 'bimm-env-');
        config()->set('environment-settings.path', $this->environmentPath);
        $this->writeEnvironment();
    }

    protected function tearDown(): void
    {
        @unlink($this->environmentPath);
        @unlink($this->environmentPath.'.tmp');

        parent::tearDown();
    }

    /** @param  array<string, string>  $overrides */
    private function writeEnvironment(array $overrides = []): void
    {
        $values = array_merge([
            'QUEUE_CONNECTION' => 'database',
            'PRODUCT_MODERATION_AI_ENABLED' => 'false',
            'PRODUCT_MODERATION_AI_MODE' => 'shadow',
            'PRODUCT_MODERATION_QUEUE' => 'moderation',
            'PRODUCT_MODERATION_TIMEOUT' => '60',
            'PRODUCT_MODERATION_MAX_ATTEMPTS' => '3',
            'PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK' => '0.10',
            'PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES' => '',
            'OPENROUTER_API_KEY' => 'existing-secret',
            'OPENROUTER_TEXT_MODEL' => 'openai/gpt-4o-mini',
            'OPENROUTER_IMAGE_MODEL' => 'openai/gpt-4o-mini',
            'AI_MODERATION_MAX_OUTPUT_TOKENS' => '2048',
            'AI_MODERATION_RETRY_TIMES' => '2',
            'AI_MODERATION_RETRY_SLEEP_MS' => '500',
            'AI_MODERATION_REQUESTS_PER_MINUTE' => '5',
            'AI_MODERATION_RATE_LIMIT_SAFETY_SECONDS' => '1',
            'AI_MODERATION_HOURLY_REQUEST_LIMIT' => '60',
            'AI_MODERATION_DAILY_REQUEST_LIMIT' => '500',
            'AI_IMAGE_MODERATION_ENABLED' => 'false',
            'AI_IMAGE_MODERATION_QUEUE' => 'moderation',
            'AI_IMAGE_MODERATION_TIMEOUT' => '60',
            'AI_IMAGE_MODERATION_MAX_ATTEMPTS' => '3',
        ], $overrides);

        file_put_contents($this->environmentPath, implode(PHP_EOL, array_map(
            fn (string $key, string $value): string => $key.'='.$value,
            array_keys($values),
            $values,
        )).PHP_EOL);
    }

    private function environment(): string
    {
        return (string) file_get_contents($this->environmentPath);
    }

    private function createAdministrator(): User
    {
        $role = Role::query()->create(['name' => 'admin-'.uniqid(), 'display_name' => 'Администратор']);
        $permission = Permission::query()->create(['key' => 'browse_admin', 'table_name' => null]);
        $role->permissions()->attach($permission);

        return User::factory()->create(['role_id' => $role->id]);
    }

    // ------------------------------------------------------------------ как выглядит страница

    public function test_page_leads_with_one_plain_decision_and_hides_technical_settings(): void
    {
        $this->actingAs($this->createAdministrator())
            ->get('/admin/moderation-settings')
            ->assertOk()
            ->assertSee('Как проверять новые товары')
            ->assertSee('Только вручную')
            ->assertSee('ИИ подсказывает, решает человек')
            ->assertSee('ИИ публикует безопасные товары сам')
            ->assertSee('Подключение к OpenRouter')
            ->assertSee('Фото товаров')
            ->assertSee('Дополнительные настройки')
            ->assertSee('Для специалиста по серверу')
            // прежний жаргон со страницы ушёл
            ->assertDontSee('Теневой')
            ->assertDontSee('Режим работы')
            ->assertDontSee('Протокол API')
            ->assertDontSee('Краткая инструкция')
            ->assertDontSee('Native moderation endpoint');
    }

    public function test_technical_settings_live_inside_a_collapsed_section(): void
    {
        $html = $this->actingAs($this->createAdministrator())
            ->get('/admin/moderation-settings')
            ->assertOk()
            ->getContent();

        $section = strpos($html, 'id="moderation-advanced"');
        $this->assertNotFalse($section, 'Блок «Дополнительные настройки» должен быть на странице');

        foreach (['Название очереди', 'Максимум запросов в сутки', 'Название очереди для фото', 'Пауза между повторами'] as $label) {
            $position = strpos($html, $label);
            $this->assertNotFalse($position, "Поле «{$label}» должно оставаться доступным");
            $this->assertGreaterThan($section, $position, "Поле «{$label}» должно быть внутри блока «Дополнительные настройки»");
        }
    }

    public function test_sync_queue_is_offered_only_while_it_is_the_current_value(): void
    {
        $this->actingAs($this->createAdministrator());

        $this->get('/admin/moderation-settings')->assertDontSee('Sync —');

        $this->writeEnvironment(['QUEUE_CONNECTION' => 'sync']);

        $this->get('/admin/moderation-settings')->assertSee('Sync — только для отладки на своём компьютере');
    }

    public function test_saved_api_keys_never_reach_the_page(): void
    {
        $this->actingAs($this->createAdministrator())
            ->get('/admin/moderation-settings')
            ->assertOk()
            ->assertDontSee('existing-secret');

        Livewire::test(ModerationSettings::class)
            ->assertSet('data.api_key', null);
    }

    // ------------------------------------------------------------------ загрузка настроек в форму

    public function test_saved_settings_are_translated_to_the_plain_choices(): void
    {
        $this->writeEnvironment([
            'PRODUCT_MODERATION_AI_ENABLED' => 'true',
            'PRODUCT_MODERATION_AI_MODE' => 'auto',
            'PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK' => '0.10',
            'PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES' => 'weapons, medicine',
        ]);
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->assertSet('data.strategy', 'auto')
            ->assertSet('data.strictness', 'normal')
            ->assertSet('data.excluded_categories', ['weapons', 'medicine']);
    }

    public function test_shadow_mode_is_shown_as_assistant_and_disabled_ai_as_manual(): void
    {
        $this->actingAs($this->createAdministrator());

        $this->writeEnvironment(['PRODUCT_MODERATION_AI_ENABLED' => 'true', 'PRODUCT_MODERATION_AI_MODE' => 'shadow']);
        Livewire::test(ModerationSettings::class)->assertSet('data.strategy', 'assist');

        $this->writeEnvironment(['PRODUCT_MODERATION_AI_ENABLED' => 'false', 'PRODUCT_MODERATION_AI_MODE' => 'auto']);
        Livewire::test(ModerationSettings::class)->assertSet('data.strategy', 'manual');
    }

    public function test_unusual_risk_threshold_is_kept_as_custom_value(): void
    {
        $this->writeEnvironment(['PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK' => '0.07']);
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->assertSet('data.strictness', 'custom')
            ->assertSet('data.max_risk', 7.0);
    }

    // ------------------------------------------------------------------ сохранение выбора

    public function test_choosing_assistant_saves_enabled_shadow_mode(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm(['strategy' => 'assist'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertStringContainsString('PRODUCT_MODERATION_AI_ENABLED=true', $this->environment());
        $this->assertStringContainsString('PRODUCT_MODERATION_AI_MODE=shadow', $this->environment());
    }

    public function test_choosing_manual_turns_ai_off_without_touching_the_saved_mode(): void
    {
        $this->writeEnvironment(['PRODUCT_MODERATION_AI_ENABLED' => 'true', 'PRODUCT_MODERATION_AI_MODE' => 'auto']);
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm(['strategy' => 'manual'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertStringContainsString('PRODUCT_MODERATION_AI_ENABLED=false', $this->environment());
        $this->assertStringContainsString('PRODUCT_MODERATION_AI_MODE=auto', $this->environment());
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function strictnessPresets(): array
    {
        return ['strict' => ['strict', '0.05'], 'normal' => ['normal', '0.1'], 'soft' => ['soft', '0.25']];
    }

    /** @dataProvider strictnessPresets */
    public function test_strictness_choice_sets_the_risk_threshold(string $strictness, string $expected): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm(['strategy' => 'auto', 'strictness' => $strictness])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertStringContainsString('PRODUCT_MODERATION_AI_MODE=auto', $this->environment());
        $this->assertStringContainsString('PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK='.$expected, $this->environment());
    }

    public function test_custom_risk_value_is_saved(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm(['strategy' => 'auto', 'strictness' => 'custom', 'max_risk' => 7])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertStringContainsString('PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK=0.07', $this->environment());
    }

    public function test_categories_are_picked_from_the_catalog_and_saved_as_aliases(): void
    {
        $parent = Category::factory()->create(['name' => 'Электроника', 'alias' => 'electronics']);
        Category::factory()->create(['name' => 'Лекарства', 'alias' => 'medicine', 'parent_id' => $parent->id]);

        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm(['strategy' => 'auto', 'strictness' => 'normal', 'excluded_categories' => ['medicine', 'electronics']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertStringContainsString(
            'PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES=medicine,electronics',
            $this->environment(),
        );
    }

    public function test_category_list_shows_the_parent_in_the_name(): void
    {
        $parent = Category::factory()->create(['name' => 'Электроника', 'alias' => 'electronics']);
        Category::factory()->create(['name' => 'Телефоны', 'alias' => 'phones', 'parent_id' => $parent->id]);

        $this->writeEnvironment([
            'PRODUCT_MODERATION_AI_ENABLED' => 'true',
            'PRODUCT_MODERATION_AI_MODE' => 'auto',
            'PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES' => 'phones, removed-category',
        ]);

        $this->actingAs($this->createAdministrator());

        $options = Livewire::test(ModerationSettings::class)
            ->instance()
            ->form
            ->getFlatFields(withHidden: true)['excluded_categories']
            ->getOptions();

        $this->assertSame('Электроника → Телефоны', $options['phones']);
        $this->assertSame('Электроника', $options['electronics']);
        // категория, которой уже нет в каталоге, не пропадает молча
        $this->assertSame('removed-category', $options['removed-category']);
    }

    public function test_switching_away_from_auto_keeps_strictness_and_categories_for_later(): void
    {
        $this->writeEnvironment([
            'PRODUCT_MODERATION_AI_ENABLED' => 'true',
            'PRODUCT_MODERATION_AI_MODE' => 'auto',
            'PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK' => '0.25',
            'PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES' => 'weapons,medicine',
        ]);
        $this->actingAs($this->createAdministrator());

        // блок «Когда ИИ может публиковать сам» скрыт, но его значения сохраняются
        Livewire::test(ModerationSettings::class)
            ->fillForm(['strategy' => 'assist'])
            ->call('save')
            ->assertHasNoFormErrors();

        $contents = $this->environment();
        $this->assertStringContainsString('PRODUCT_MODERATION_AI_MODE=shadow', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK=0.25', $contents);
        $this->assertStringContainsString('PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES=weapons,medicine', $contents);
    }

    // ------------------------------------------------------------------ ошибки и защита

    public function test_enabling_ai_without_a_key_explains_what_to_do(): void
    {
        $this->writeEnvironment(['OPENROUTER_API_KEY' => '']);
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm(['strategy' => 'assist'])
            ->call('save')
            ->assertHasFormErrors(['api_key'])
            ->assertNotified('Настройки не сохранены');

        $this->assertStringContainsString('PRODUCT_MODERATION_AI_ENABLED=false', $this->environment());
    }

    public function test_error_in_a_collapsed_field_opens_the_advanced_section(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm(['daily_request_limit' => null])
            ->call('save')
            ->assertHasFormErrors(['daily_request_limit'])
            ->assertNotified('Настройки не сохранены')
            ->assertDispatched('open-section', id: 'moderation-advanced');
    }

    public function test_failed_save_shows_the_error_message_only_once(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm(['daily_request_limit' => null])
            ->call('save')
            ->assertHasFormErrors(['daily_request_limit']);

        $this->assertCount(1, (array) session()->get('filament.notifications', []));
    }

    public function test_error_in_a_main_field_does_not_open_the_advanced_section(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(ModerationSettings::class)
            ->fillForm(['text_model' => ''])
            ->call('save')
            ->assertHasFormErrors(['text_model'])
            ->assertNotDispatched('open-section');
    }

    // ------------------------------------------------------------------ строка состояния

    public function test_status_line_says_ai_is_off_when_nothing_is_enabled(): void
    {
        $this->actingAs($this->createAdministrator())
            ->get('/admin/moderation-settings')
            ->assertOk()
            ->assertSee('data-moderation-status="off"', false)
            ->assertSee('ИИ-модерация выключена')
            ->assertSee('Каждый новый товар проверяет модератор вручную.');
    }

    public function test_status_line_reports_a_missing_key_as_an_error(): void
    {
        $this->writeEnvironment(['PRODUCT_MODERATION_AI_ENABLED' => 'true', 'OPENROUTER_API_KEY' => '']);

        $this->actingAs($this->createAdministrator())
            ->get('/admin/moderation-settings')
            ->assertOk()
            ->assertSee('data-moderation-status="error"', false)
            ->assertSee('Не задан ключ OpenRouter');
    }

    public function test_status_line_confirms_a_working_setup(): void
    {
        $this->writeEnvironment(['PRODUCT_MODERATION_AI_ENABLED' => 'true']);

        $this->actingAs($this->createAdministrator())
            ->get('/admin/moderation-settings')
            ->assertOk()
            ->assertSee('data-moderation-status="ok"', false)
            ->assertSee('Работает')
            ->assertSee('Запросов к ИИ сегодня:');
    }
}
