<x-filament-panels::page>
    <x-filament::section>
        <div class="space-y-2">
            @forelse ($tree as $category)
                @include('filament.pages.partials.category-tree-node', ['category' => $category])
            @empty
                <p class="text-sm text-gray-500">Категории отсутствуют.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>
