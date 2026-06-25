@php($details = app(\App\Services\ModerationRunDetailsPresenter::class)->present($run))

<div class="space-y-5">
    <dl class="grid grid-cols-1 gap-3 md:grid-cols-2">
        @foreach($details['summary'] as $item)
            <div>
                <dt class="text-sm font-medium text-gray-500">{{ $item['label'] }}</dt>
                <dd class="text-sm text-gray-950 dark:text-white">{{ filled($item['value']) ? $item['value'] : '—' }}</dd>
            </div>
        @endforeach
    </dl>

    @if(filled($details['error']))
        <div class="rounded-lg border border-danger-200 bg-danger-50 p-3 text-sm text-danger-700">
            <div class="font-semibold">Ошибка API</div>
            <div>{{ $details['error'] }}</div>
        </div>
    @endif

    @if(count($details['reasons']) > 0)
        <div>
            <h3 class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">Причины решения</h3>
            <div class="space-y-2">
                @foreach($details['reasons'] as $reason)
                    <div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <div class="font-medium">
                            {{ $reason['code'] ?? 'Причина' }}
                            @if($reason['image_index'] !== null)
                                <span class="text-gray-500"> · фото №{{ $reason['image_index'] }}</span>
                            @endif
                        </div>
                        @if(filled($reason['field']))
                            <div class="mt-1 text-gray-500">Поле: {{ $reason['field'] }}</div>
                        @endif
                        @if(filled($reason['evidence']))
                            <div class="mt-2">{{ $reason['evidence'] }}</div>
                        @endif
                        @if(filled($reason['suggestion']))
                            <div class="mt-2 text-warning-700">Рекомендация: {{ $reason['suggestion'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(count($details['imageResult']) > 0)
        <div>
            <h3 class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">Данные проверки фото</h3>
            <pre class="max-h-80 overflow-auto rounded-lg bg-gray-50 p-3 text-xs text-gray-800 dark:bg-gray-900 dark:text-gray-100">{{ json_encode($details['imageResult'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endif

    @if(count($details['textResult']) > 0)
        <div>
            <h3 class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">Данные проверки текста</h3>
            <pre class="max-h-80 overflow-auto rounded-lg bg-gray-50 p-3 text-xs text-gray-800 dark:bg-gray-900 dark:text-gray-100">{{ json_encode($details['textResult'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endif
</div>
