<?php

namespace App\Services;

/**
 * Переводит настройки модерации между «человеческой» формой в админке и тем, что хранит `.env`.
 *
 * В форме администратор выбирает одно из трёх решений («как проверять товары») и одну из степеней строгости,
 * а в хранилище по-прежнему лежат `ai_enabled`, `mode` и `max_risk`: `ModerationSettingsWriter` и всё остальное
 * приложение работают с ними без изменений.
 */
class ModerationSettingsFormMapper
{
    public const STRATEGY_MANUAL = 'manual';

    public const STRATEGY_ASSIST = 'assist';

    public const STRATEGY_AUTO = 'auto';

    public const STRICTNESS_CUSTOM = 'custom';

    /** Степень строгости → максимальный риск для автопубликации, %. */
    public const STRICTNESS_PRESETS = ['strict' => 5.0, 'normal' => 10.0, 'soft' => 25.0];

    private const DEFAULT_RISK = 10.0;

    /**
     * Данные из хранилища → состояние формы.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function toForm(array $data): array
    {
        $data['strategy'] = $this->strategyFor((bool) ($data['ai_enabled'] ?? false), (string) ($data['mode'] ?? 'shadow'));
        $data['strictness'] = $this->strictnessFor($this->risk($data['max_risk'] ?? null));
        $data['excluded_categories'] = $this->aliases($data['excluded_categories'] ?? []);

        return $data;
    }

    /**
     * Состояние формы → данные для `ModerationSettingsWriter`.
     *
     * @param  array<string, mixed>  $state  то, что вернула форма
     * @param  array<string, mixed>  $current  сохранённые сейчас настройки (в формате хранилища): из них
     *                                        берутся значения, которых форма не прислала
     * @return array<string, mixed>
     */
    public function toStorage(array $state, array $current = []): array
    {
        $strategy = (string) ($state['strategy'] ?? $this->strategyFor((bool) ($current['ai_enabled'] ?? false), (string) ($current['mode'] ?? 'shadow')));
        $currentMode = (string) ($current['mode'] ?? 'shadow');

        $state['ai_enabled'] = $strategy !== self::STRATEGY_MANUAL;
        $state['mode'] = match ($strategy) {
            self::STRATEGY_AUTO => 'auto',
            self::STRATEGY_ASSIST => 'shadow',
            // ИИ выключен: режим не меняем, чтобы не переписывать то, что админ мог настроить раньше
            default => in_array($currentMode, ['shadow', 'auto'], true) ? $currentMode : 'shadow',
        };

        $strictness = (string) ($state['strictness'] ?? '');
        $state['max_risk'] = array_key_exists($strictness, self::STRICTNESS_PRESETS)
            ? self::STRICTNESS_PRESETS[$strictness]
            : $this->risk($state['max_risk'] ?? $current['max_risk'] ?? null);

        $state['excluded_categories'] = implode(',', $this->aliases(
            array_key_exists('excluded_categories', $state) ? $state['excluded_categories'] : ($current['excluded_categories'] ?? [])
        ));

        unset($state['strategy'], $state['strictness']);

        return $state;
    }

    public function strategyFor(bool $aiEnabled, string $mode): string
    {
        if (! $aiEnabled) {
            return self::STRATEGY_MANUAL;
        }

        return $mode === 'auto' ? self::STRATEGY_AUTO : self::STRATEGY_ASSIST;
    }

    public function strictnessFor(float $risk): string
    {
        foreach (self::STRICTNESS_PRESETS as $name => $value) {
            if (abs($risk - $value) < 0.01) {
                return $name;
            }
        }

        return self::STRICTNESS_CUSTOM;
    }

    /**
     * Алиасы категорий: из строки «a, b» или массива, без пустых и повторов.
     *
     * @return array<int, string>
     */
    public function aliases(mixed $value): array
    {
        $items = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $item): string => trim(str_replace(',', '', (string) $item)),
            $items,
        ), static fn (string $alias): bool => $alias !== '')));
    }

    private function risk(mixed $value): float
    {
        if (! is_numeric($value)) {
            return self::DEFAULT_RISK;
        }

        return max(0.0, min(100.0, (float) $value));
    }
}
