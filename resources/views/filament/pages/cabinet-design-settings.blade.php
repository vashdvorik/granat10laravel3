<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Кабинет продавца</x-slot>
        <x-slot name="description">
            Переключатель влияет только на `/account/*`. Публичный сайт использует отдельный переключатель дизайна.
        </x-slot>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit">
                    Сохранить и включить
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
