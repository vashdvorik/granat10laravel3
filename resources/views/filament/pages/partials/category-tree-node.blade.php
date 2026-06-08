<details class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900" open>
    <summary class="flex cursor-pointer items-center justify-between gap-4">
        <span class="font-medium text-gray-950 dark:text-white">
            {{ $category->name }}
        </span>

        <span class="flex items-center gap-3 text-xs text-gray-500">
            <span>{{ $category->products_count }} товаров</span>
            <span>{{ $category->is_active ? 'Активна' : 'Скрыта' }}</span>
            <a
                class="text-primary-600 hover:underline"
                href="{{ \App\Filament\Resources\CategoryResource::getUrl('edit', ['record' => $category]) }}"
            >
                Редактировать
            </a>
        </span>
    </summary>

    @if ($category->children->isNotEmpty())
        <div class="mt-3 space-y-2 border-l border-gray-200 pl-4 dark:border-gray-700">
            @foreach ($category->children as $child)
                @include('filament.pages.partials.category-tree-node', ['category' => $child])
            @endforeach
        </div>
    @endif
</details>
