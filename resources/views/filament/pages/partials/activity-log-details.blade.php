<div class="space-y-4 text-sm">
    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-[10rem_1fr]">
        <dt class="font-medium text-gray-950 dark:text-white">Группа</dt>
        <dd>{{ $activity->log_name ?? '—' }}</dd>

        <dt class="font-medium text-gray-950 dark:text-white">Действие</dt>
        <dd>{{ $activity->description }}</dd>

        <dt class="font-medium text-gray-950 dark:text-white">Событие</dt>
        <dd>{{ $activity->event ?? '—' }}</dd>

        <dt class="font-medium text-gray-950 dark:text-white">Инициатор</dt>
        <dd>
            @if ($activity->causer)
                {{ $activity->causer->name }} ({{ $activity->causer->email }}) — ID:
                {{ $activity->causer_id }}
            @else
                Система
            @endif
        </dd>

        <dt class="font-medium text-gray-950 dark:text-white">Объект</dt>
        <dd>
            @if ($activity->subject_type)
                {{ $activity->subject_type }} #{{ $activity->subject_id }}
            @else
                —
            @endif
        </dd>

        <dt class="font-medium text-gray-950 dark:text-white">IP</dt>
        <dd>{{ $activity->properties->get('ip') ?? '—' }}</dd>

        <dt class="font-medium text-gray-950 dark:text-white">Дата</dt>
        <dd>{{ $activity->created_at->format('d.m.Y H:i:s') }}</dd>
    </dl>

    <div>
        <h3 class="mb-2 font-medium text-gray-950 dark:text-white">Свойства</h3>

        @if ($activity->properties->isNotEmpty())
            <pre class="max-h-96 overflow-auto rounded-lg bg-gray-100 p-4 text-xs text-gray-950 dark:bg-gray-950 dark:text-gray-100">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        @else
            <p class="text-gray-500">Дополнительные свойства отсутствуют.</p>
        @endif
    </div>
</div>
