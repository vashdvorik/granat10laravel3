<?php

namespace App\Services;

use App\Data\ModerationSettingsSnapshot;
use Illuminate\Support\Str;

/**
 * Состояние ИИ-модерации одной фразой для верха страницы настроек: работает ли, и если нет — что чинить.
 * Учитывает сохранённые настройки, а не то, что сейчас набрано в форме.
 */
class ModerationStatusSummary
{
    public const OK = 'ok';

    public const WARNING = 'warning';

    public const ERROR = 'error';

    public const OFF = 'off';

    /**
     * @return array{level: string, headline: string, lines: array<int, string>, notes: array<int, string>}
     */
    public function make(ModerationSettingsSnapshot $snapshot, bool $production = false): array
    {
        $data = $snapshot->data;
        $ops = $snapshot->operationsStatus;
        $budget = $snapshot->budgetUsage;

        $textOn = (bool) ($data['ai_enabled'] ?? false);
        $imageOn = (bool) ($data['image_moderation_enabled'] ?? false);

        if (! $textOn && ! $imageOn) {
            return [
                'level' => self::OFF,
                'headline' => 'ИИ-модерация выключена',
                'lines' => ['Каждый новый товар проверяет модератор вручную.'],
                'notes' => [],
            ];
        }

        $problems = [];
        $warnings = [];

        if ($textOn && ! $snapshot->textApiKeyConfigured) {
            $problems[] = 'Не задан ключ ИИ для проверки текста: товары будут ждать модератора.';
        }

        if ($imageOn && ! $snapshot->imageApiKeyConfigured) {
            $problems[] = 'Не задан ключ для проверки фото: товары с фото не смогут пройти проверку.';
        }

        if (! (bool) ($ops['queue_ok'] ?? false)) {
            $problems[] = 'Не запущен обработчик очереди: проверки не будут выполняться. Сообщите тому, кто отвечает за сервер.';
        } elseif ($production && ($data['queue_connection'] ?? null) === 'sync') {
            $warnings[] = 'Очередь работает в режиме Sync: проверка идёт прямо во время действий продавца и замедляет сайт. Переключите на Database в дополнительных настройках.';
        }

        if ($this->exhausted($budget, 'daily')) {
            $warnings[] = 'Дневной лимит запросов к ИИ исчерпан: до завтра новые товары идут на ручную проверку.';
        } elseif ($this->exhausted($budget, 'hourly')) {
            $warnings[] = 'Часовой лимит запросов к ИИ исчерпан: до начала следующего часа новые товары идут на ручную проверку.';
        }

        $stuck = (int) ($ops['stuck_runs'] ?? 0);

        if ($stuck > 0) {
            $warnings[] = 'Есть зависшие проверки: '.$stuck.'. Обычно помогает перезапуск обработчика очереди.';
        }

        $issues = array_merge($problems, $warnings);
        $level = $problems !== [] ? self::ERROR : ($warnings !== [] ? self::WARNING : self::OK);
        $headline = $level === self::OK ? 'Работает' : array_shift($issues);

        $lines = array_merge($issues, [$this->describe($data, $textOn, $imageOn)]);

        if ($level === self::OK) {
            $lines[] = $this->usage($budget);
        }

        $notes = [];
        $failed = (int) ($ops['failed_runs'] ?? 0);

        if ($failed > 0) {
            $notes[] = 'Неудачных проверок за всё время: '.$failed.'.';
        }

        return [
            'level' => $level,
            'headline' => $headline,
            'lines' => array_values(array_filter($lines)),
            'notes' => $notes,
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function describe(array $data, bool $textOn, bool $imageOn): string
    {
        $parts = [];

        if ($textOn) {
            $parts[] = ($data['mode'] ?? 'shadow') === 'auto'
                ? 'ИИ публикует безопасные товары сам (риск не выше '.$this->percent($data['max_risk'] ?? 10).'%)'
                : 'ИИ подсказывает модератору, публикует человек';
        } else {
            $parts[] = 'текст проверяет модератор';
        }

        $parts[] = $imageOn ? 'фото проверяются ИИ' : 'фото ИИ не проверяет';

        return Str::ucfirst(implode('; ', $parts)).'.';
    }

    /** @param  array<string, mixed>  $budget */
    private function usage(array $budget): string
    {
        $daily = (int) ($budget['daily_limit'] ?? 0);

        if ($daily <= 0) {
            return '';
        }

        return 'Запросов к ИИ сегодня: '.(int) ($budget['daily_used'] ?? 0).' из '.$daily.'.';
    }

    /** @param  array<string, mixed>  $budget */
    private function exhausted(array $budget, string $period): bool
    {
        $limit = (int) ($budget[$period.'_limit'] ?? 0);

        return $limit > 0 && (int) ($budget[$period.'_used'] ?? 0) >= $limit;
    }

    private function percent(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }
}
