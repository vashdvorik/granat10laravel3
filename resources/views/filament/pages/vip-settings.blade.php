<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Стоимость VIP</x-slot>
        <x-slot name="description">
            VIP покупается продавцом для отдельного товара за деньги с баланса. Доступные сроки: 1 день, 7 дней, месяц и год.
        </x-slot>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit">
                    Сохранить
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Сейчас продавцы платят</x-slot>
        <x-slot name="description">Цены по текущей сохранённой стоимости дня.</x-slot>

        <ul class="space-y-1">
            @foreach ($this->getPreview() as $option)
                <li>{{ $option['label'] }} — <strong>{{ $option['price'] }} руб</strong></li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-panels::page>
