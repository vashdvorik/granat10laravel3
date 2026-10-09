@php
    $summary = $statusSummary ?: ['level' => 'off', 'headline' => '', 'lines' => [], 'notes' => []];
    $palette = [
        'ok' => ['#16a34a', 'heroicon-m-check-circle'],
        'warning' => ['#d97706', 'heroicon-m-exclamation-triangle'],
        'error' => ['#dc2626', 'heroicon-m-x-circle'],
        'off' => ['#6b7280', 'heroicon-m-pause-circle'],
    ];
    [$color, $icon] = $palette[$summary['level']] ?? $palette['off'];
@endphp

<x-filament-panels::page>
    {{-- Состояние одной фразой: работает ли ИИ-модерация и, если нет, что починить --}}
    <div data-moderation-status="{{ $summary['level'] }}"
         style="display:flex;gap:.875rem;align-items:flex-start;padding:1rem 1.25rem;border-radius:.75rem;border:1px solid {{ $color }}55;border-left:5px solid {{ $color }};background:{{ $color }}12;">
        <x-filament::icon :icon="$icon" class="h-7 w-7" style="color:{{ $color }};flex:none" />
        <div>
            <div style="font-weight:600;font-size:1.05rem;line-height:1.4;color:{{ $color }}">{{ $summary['headline'] }}</div>
            @foreach ($summary['lines'] as $line)
                <div style="font-size:.92rem;line-height:1.5;margin-top:.2rem">{{ $line }}</div>
            @endforeach
            @foreach ($summary['notes'] as $note)
                <div style="font-size:.85rem;line-height:1.5;margin-top:.2rem;opacity:.7">{{ $note }}</div>
            @endforeach
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            Сохранить настройки
        </x-filament::button>
    </form>

    {{-- Подробные цифры: нужны редко, поэтому свёрнуты --}}
    <x-filament::section collapsible collapsed compact>
        <x-slot name="heading">Подробная статистика</x-slot>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <div class="text-sm text-gray-500">Запросов к ИИ за текущий час</div>
                <div class="text-xl font-semibold">{{ $budgetUsage['hourly_used'] ?? 0 }} / {{ $budgetUsage['hourly_limit'] ?? 0 }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Запросов к ИИ за сегодня</div>
                <div class="text-xl font-semibold">{{ $budgetUsage['daily_used'] ?? 0 }} / {{ $budgetUsage['daily_limit'] ?? 0 }}</div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4" style="margin-top:1rem">
            <div>
                <div class="text-sm text-gray-500">Обработчик очереди</div>
                <div class="font-semibold {{ ($operationsStatus['queue_ok'] ?? false) ? 'text-success-600' : 'text-danger-600' }}">
                    {{ ($operationsStatus['queue_ok'] ?? false) ? 'Работает' : 'Не обнаружен' }}
                </div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Неудачные проверки текста</div>
                <div class="font-semibold">{{ $operationsStatus['failed_text_runs'] ?? 0 }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Зависшие проверки текста</div>
                <div class="font-semibold">{{ $operationsStatus['stuck_text_runs'] ?? 0 }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Проверка фото</div>
                <div class="font-semibold {{ ($operationsStatus['image_moderation_enabled'] ?? false) ? 'text-success-600' : 'text-gray-500' }}">
                    {{ ($operationsStatus['image_moderation_enabled'] ?? false) ? 'Включена' : 'Выключена' }}
                </div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Модель для текста</div>
                <div class="font-semibold">{{ $operationsStatus['text_model'] ?? '—' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Модель для фото</div>
                <div class="font-semibold">{{ $operationsStatus['image_model'] ?? '—' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Неудачные проверки фото</div>
                <div class="font-semibold">{{ $operationsStatus['failed_image_runs'] ?? 0 }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Зависшие проверки фото</div>
                <div class="font-semibold">{{ $operationsStatus['stuck_image_runs'] ?? 0 }}</div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
