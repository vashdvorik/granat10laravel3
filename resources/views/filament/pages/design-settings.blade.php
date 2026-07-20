<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Публичный сайт</x-slot>
        <x-slot name="description">
            Выбор применяется глобально для всех посетителей. Бизнес-логика, маршруты и данные не меняются.
        </x-slot>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit">
                    Сохранить и включить
                </x-filament::button>

                <x-filament::button tag="a" href="{{ route('home') }}" target="_blank" color="gray">
                    Открыть сайт
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
