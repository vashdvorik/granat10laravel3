<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Запросы за текущий час</x-slot>
            <div class="text-2xl font-semibold">
                {{ $budgetUsage['hourly_used'] ?? 0 }} / {{ $budgetUsage['hourly_limit'] ?? 0 }}
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Запросы за сегодня</x-slot>
            <div class="text-2xl font-semibold">
                {{ $budgetUsage['daily_used'] ?? 0 }} / {{ $budgetUsage['daily_limit'] ?? 0 }}
            </div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Состояние модерации</x-slot>
        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <div class="text-sm text-gray-500">Обработчик очереди</div>
                <div class="font-semibold {{ ($operationsStatus['queue_ok'] ?? false) ? 'text-success-600' : 'text-danger-600' }}">
                    {{ ($operationsStatus['queue_ok'] ?? false) ? 'Работает' : 'Не обнаружен' }}
                </div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Неудачные проверки</div>
                <div class="font-semibold">{{ $operationsStatus['failed_text_runs'] ?? 0 }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Зависшие проверки</div>
                <div class="font-semibold">{{ $operationsStatus['stuck_text_runs'] ?? 0 }}</div>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Готовность проверки фото</x-slot>
        <div class="grid gap-3 md:grid-cols-4">
            <div>
                <div class="text-sm text-gray-500">Статус</div>
                <div class="font-semibold {{ ($operationsStatus['image_moderation_enabled'] ?? false) ? 'text-success-600' : 'text-gray-500' }}">
                    {{ ($operationsStatus['image_moderation_enabled'] ?? false) ? 'Включена' : 'Выключена' }}
                </div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Провайдер</div>
                <div class="font-semibold">{{ $operationsStatus['image_provider'] ?? '—' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">API-ключ</div>
                <div class="font-semibold {{ ($operationsStatus['image_api_key_configured'] ?? false) ? 'text-success-600' : 'text-danger-600' }}">
                    {{ ($operationsStatus['image_api_key_configured'] ?? false) ? 'Настроен' : 'Не задан' }}
                </div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Модель</div>
                <div class="font-semibold">{{ $operationsStatus['image_model'] ?? '—' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Очередь</div>
                <div class="font-semibold">{{ $operationsStatus['image_queue'] ?? '—' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Ошибки фото</div>
                <div class="font-semibold">{{ $operationsStatus['failed_image_runs'] ?? 0 }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Зависшие фото</div>
                <div class="font-semibold">{{ $operationsStatus['stuck_image_runs'] ?? 0 }}</div>
            </div>
        </div>
    </x-filament::section>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            Сохранить настройки
        </x-filament::button>
        <x-filament::button type="button" color="gray" wire:click="testTextConnection">
            Проверить подключение текста
        </x-filament::button>
        <x-filament::button type="button" color="gray" wire:click="testImageConnection">
            Проверить подключение изображений
        </x-filament::button>
    </form>
</x-filament-panels::page>
