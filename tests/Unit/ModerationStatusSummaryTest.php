<?php

namespace Tests\Unit;

use App\Data\ModerationSettingsSnapshot;
use App\Services\ModerationStatusSummary;
use PHPUnit\Framework\TestCase;

class ModerationStatusSummaryTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $ops
     * @param  array<string, mixed>  $budget
     */
    private function snapshot(array $data = [], array $ops = [], array $budget = [], bool $textKey = true, bool $imageKey = true): ModerationSettingsSnapshot
    {
        return new ModerationSettingsSnapshot(
            data: array_merge([
                'ai_enabled' => true, 'mode' => 'shadow', 'max_risk' => 10.0,
                'image_moderation_enabled' => false, 'queue_connection' => 'database',
            ], $data),
            apiKeyConfigured: $textKey || $imageKey,
            textApiKeyConfigured: $textKey,
            imageApiKeyConfigured: $imageKey,
            budgetUsage: array_merge(['hourly_used' => 3, 'hourly_limit' => 60, 'daily_used' => 12, 'daily_limit' => 500], $budget),
            operationsStatus: array_merge(['queue_ok' => true, 'stuck_runs' => 0, 'failed_runs' => 0], $ops),
        );
    }

    private function summary(ModerationSettingsSnapshot $snapshot, bool $production = false): array
    {
        return (new ModerationStatusSummary())->make($snapshot, $production);
    }

    public function test_everything_off_means_manual_moderation(): void
    {
        $result = $this->summary($this->snapshot(['ai_enabled' => false]));

        $this->assertSame('off', $result['level']);
        $this->assertSame('ИИ-модерация выключена', $result['headline']);
        $this->assertSame(['Каждый новый товар проверяет модератор вручную.'], $result['lines']);
    }

    public function test_working_setup_reports_mode_and_daily_usage(): void
    {
        $result = $this->summary($this->snapshot(['mode' => 'auto', 'max_risk' => 5.0, 'image_moderation_enabled' => true]));

        $this->assertSame('ok', $result['level']);
        $this->assertSame('Работает', $result['headline']);
        $this->assertContains('ИИ публикует безопасные товары сам (риск не выше 5%); фото проверяются ИИ.', $result['lines']);
        $this->assertContains('Запросов к ИИ сегодня: 12 из 500.', $result['lines']);
    }

    public function test_assistant_mode_is_described_without_jargon(): void
    {
        $result = $this->summary($this->snapshot());

        $this->assertContains('ИИ подсказывает модератору, публикует человек; фото ИИ не проверяет.', $result['lines']);
    }

    public function test_only_photo_check_enabled_still_counts_as_running(): void
    {
        $result = $this->summary($this->snapshot(['ai_enabled' => false, 'image_moderation_enabled' => true]));

        $this->assertSame('ok', $result['level']);
        $this->assertContains('Текст проверяет модератор; фото проверяются ИИ.', $result['lines']);
    }

    public function test_missing_text_key_is_an_error_that_leads(): void
    {
        $result = $this->summary($this->snapshot(textKey: false));

        $this->assertSame('error', $result['level']);
        $this->assertStringContainsString('Не задан ключ ИИ для проверки текста', $result['headline']);
    }

    public function test_missing_photo_key_matters_only_when_photo_check_is_on(): void
    {
        $off = $this->summary($this->snapshot(imageKey: false));
        $on = $this->summary($this->snapshot(['image_moderation_enabled' => true], imageKey: false));

        $this->assertSame('ok', $off['level']);
        $this->assertSame('error', $on['level']);
        $this->assertStringContainsString('ключ для проверки фото', $on['headline']);
    }

    public function test_dead_queue_worker_is_an_error(): void
    {
        $result = $this->summary($this->snapshot(ops: ['queue_ok' => false]));

        $this->assertSame('error', $result['level']);
        $this->assertStringContainsString('Не запущен обработчик очереди', $result['headline']);
    }

    public function test_exhausted_daily_limit_is_a_warning(): void
    {
        $result = $this->summary($this->snapshot(budget: ['daily_used' => 500]));

        $this->assertSame('warning', $result['level']);
        $this->assertStringContainsString('Дневной лимит', $result['headline']);
    }

    public function test_exhausted_hourly_limit_is_a_warning(): void
    {
        $result = $this->summary($this->snapshot(budget: ['hourly_used' => 60]));

        $this->assertSame('warning', $result['level']);
        $this->assertStringContainsString('Часовой лимит', $result['headline']);
    }

    public function test_stuck_checks_are_a_warning_but_old_failures_are_only_a_note(): void
    {
        $stuck = $this->summary($this->snapshot(ops: ['stuck_runs' => 2]));
        $failed = $this->summary($this->snapshot(ops: ['failed_runs' => 4]));

        $this->assertSame('warning', $stuck['level']);
        $this->assertStringContainsString('зависшие проверки: 2', $stuck['headline']);

        $this->assertSame('ok', $failed['level']);
        $this->assertSame(['Неудачных проверок за всё время: 4.'], $failed['notes']);
    }

    public function test_sync_queue_warns_only_on_production(): void
    {
        $dev = $this->summary($this->snapshot(['queue_connection' => 'sync']), production: false);
        $prod = $this->summary($this->snapshot(['queue_connection' => 'sync']), production: true);

        $this->assertSame('ok', $dev['level']);
        $this->assertSame('warning', $prod['level']);
        $this->assertStringContainsString('режиме Sync', $prod['headline']);
    }

    public function test_errors_come_before_warnings(): void
    {
        $result = $this->summary($this->snapshot(ops: ['queue_ok' => false, 'stuck_runs' => 1], budget: ['daily_used' => 500]));

        $this->assertSame('error', $result['level']);
        $this->assertStringContainsString('Не запущен обработчик очереди', $result['headline']);
        $this->assertCount(3, array_filter($result['lines'], fn (string $line): bool => $line !== ''));
    }
}
