<x-filament-panels::page>
    <style>
        .cat-tree { border: 1px solid rgb(var(--gray-200)); border-radius: .75rem; background: #fff; }
        .dark .cat-tree { border-color: rgba(255, 255, 255, .1); background: rgb(var(--gray-900)); }
        .cat-row { display: flex; align-items: center; gap: .75rem; padding: .5rem .75rem; border-top: 1px solid rgb(var(--gray-100)); }
        .cat-row:first-child { border-top: 0; }
        .cat-row:hover { background: rgb(var(--gray-50)); }
        .dark .cat-row { border-color: rgba(255, 255, 255, .06); }
        .dark .cat-row:hover { background: rgba(255, 255, 255, .04); }
        .cat-row__main { display: flex; flex: 1; flex-wrap: wrap; align-items: center; gap: .15rem .6rem; min-width: 0; }
        .cat-row__counts { flex: none; min-width: 9.5rem; font-size: .75rem; line-height: 1.25; text-align: right; color: rgb(var(--gray-500)); }
        .cat-row__actions { display: flex; flex: none; align-items: center; gap: .125rem; }
        .cat-toggle { display: inline-flex; flex: none; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; border-radius: .375rem; color: rgb(var(--gray-500)); }
        button.cat-toggle:hover { background: rgb(var(--gray-100)); color: rgb(var(--gray-700)); }
        .dark button.cat-toggle:hover { background: rgba(255, 255, 255, .08); color: #fff; }
        .cat-name { font-weight: 600; color: rgb(var(--gray-950)); }
        .dark .cat-name { color: #fff; }
        .cat-alias { font-size: .75rem; color: rgb(var(--gray-500)); }
        .cat-path { flex-basis: 100%; font-size: .75rem; color: rgb(var(--gray-500)); }
        .cat-row.is-hidden .cat-name { opacity: .55; }
        .cat-row__counts strong { color: rgb(var(--gray-700)); font-weight: 600; }
        .dark .cat-row__counts strong { color: rgb(var(--gray-200)); }
        .cat-hint { font-size: .8rem; color: rgb(var(--gray-500)); }
        .cat-warning { padding: .75rem 1rem; border: 1px solid #fcd34d; border-radius: .75rem; background: #fffbeb; color: #92400e; font-size: .875rem; line-height: 1.4; }
        .dark .cat-warning { border-color: rgba(251, 191, 36, .4); background: rgba(251, 191, 36, .1); color: #fde68a; }
        .cat-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
        @media (max-width: 767px) {
            .cat-row { flex-wrap: wrap; }
            .cat-row__main { flex-basis: 100%; }
            .cat-row__counts { flex: 1; min-width: 0; text-align: left; }
        }
    </style>

    @php
        $rows = $snapshot['rows'];
        $searching = $this->search !== '' || $this->onlyProblems;
    @endphp

    <div class="cat-toolbar">
        <div style="flex: 1 1 18rem; max-width: 28rem;">
            <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                <x-filament::input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Найти по названию или адресу…"
                />
            </x-filament::input.wrapper>
        </div>

        <x-filament::button icon="heroicon-m-plus" wire:click="mountAction('addCategory')">
            Добавить категорию
        </x-filament::button>

        @if (! $searching && $this->expanded !== [])
            <x-filament::button color="gray" icon="heroicon-m-arrows-pointing-in" wire:click="collapseAll">
                Свернуть всё
            </x-filament::button>
        @endif

        @if ($searching)
            <x-filament::button color="gray" icon="heroicon-m-x-mark" wire:click="showAllCategories">
                Показать всё дерево
            </x-filament::button>
        @endif
    </div>

    @if ($snapshot['problems'] > 0 && ! $this->onlyProblems)
        <div class="cat-warning">
            <strong>Категорий с «осиротевшими» товарами: {{ $snapshot['problems'] }}.</strong>
            Товары лежат в категории, у которой есть подкатегории: продавец не сможет сохранить такую карточку.
            Перенесите их в подкатегорию (пункт «Перенести товары…» в меню строки).
            <button type="button" style="font-weight: 600; text-decoration: underline;" wire:click="$set('onlyProblems', true)">Показать эти категории</button>
        </div>
    @endif

    @if ($this->onlyProblems)
        <p class="cat-hint">Показаны только категории с «осиротевшими» товарами: {{ $snapshot['problems'] }}.</p>
    @elseif ($this->search !== '')
        <p class="cat-hint">Найдено: {{ count($rows) }}{{ $snapshot['truncated'] ? '+ (показаны первые 100, уточните запрос)' : '' }}.</p>
    @else
        <p class="cat-hint">
            Всего категорий: {{ $snapshot['total'] }}. Товары хранятся только в категориях последнего уровня.
            Порядок меняют стрелками, перенос — в меню «⋮».
        </p>
    @endif

    <div class="cat-tree">
        @forelse ($rows as $row)
            <div class="cat-row {{ $row['active'] ? '' : 'is-hidden' }}" style="padding-left: {{ 12 + $row['depth'] * 26 }}px" wire:key="cat-row-{{ $row['id'] }}">
                <div class="cat-row__main">
                    @if ($row['has_children'] && ! $searching)
                        <button type="button" class="cat-toggle" wire:click="toggle({{ $row['id'] }})" aria-label="{{ $row['expanded'] ? 'Свернуть' : 'Раскрыть' }}">
                            <x-filament::icon :icon="$row['expanded'] ? 'heroicon-m-chevron-down' : 'heroicon-m-chevron-right'" class="h-5 w-5" />
                        </button>
                    @else
                        <span class="cat-toggle"></span>
                    @endif

                    <span class="cat-name">{{ $row['name'] }}</span>
                    <span class="cat-alias">{{ $row['alias'] }}</span>

                    @unless ($row['active'])
                        <x-filament::badge color="gray" size="sm">Скрыта</x-filament::badge>
                    @endunless

                    @if ($row['problem'])
                        <x-filament::badge color="warning" size="sm" icon="heroicon-m-exclamation-triangle">Товары не в подкатегории</x-filament::badge>
                    @endif

                    @if ($row['path'])
                        <span class="cat-path">{{ $row['path'] }}</span>
                    @endif
                </div>

                <div class="cat-row__counts">
                    <div><strong>{{ $row['direct'] }}</strong> {{ \App\Support\Plural::ru($row['direct'], 'товар', 'товара', 'товаров') }}</div>
                    @if ($row['has_children'])
                        <div>{{ $row['children_count'] }} {{ \App\Support\Plural::ru($row['children_count'], 'подкатегория', 'подкатегории', 'подкатегорий') }} · всего {{ $row['total'] }}</div>
                    @endif
                </div>

                <div class="cat-row__actions">
                    @unless ($searching)
                        <x-filament::icon-button
                            icon="heroicon-m-arrow-up"
                            size="sm"
                            color="gray"
                            label="Выше"
                            tooltip="Выше"
                            :disabled="$row['first']"
                            wire:click="moveUp({{ $row['id'] }})"
                        />
                        <x-filament::icon-button
                            icon="heroicon-m-arrow-down"
                            size="sm"
                            color="gray"
                            label="Ниже"
                            tooltip="Ниже"
                            :disabled="$row['last']"
                            wire:click="moveDown({{ $row['id'] }})"
                        />
                    @endunless

                    <x-filament::icon-button
                        icon="heroicon-m-plus"
                        size="sm"
                        label="Добавить подкатегорию"
                        tooltip="Добавить подкатегорию"
                        wire:click="mountAction('addCategory', { parent: {{ $row['id'] }} })"
                    />

                    <x-filament::icon-button
                        icon="heroicon-m-pencil-square"
                        size="sm"
                        color="gray"
                        tag="a"
                        :href="\App\Filament\Resources\CategoryResource::getUrl('edit', ['record' => $row['id']])"
                        label="Изменить"
                        tooltip="Изменить: название, иконка, цвета, описание"
                    />

                    <x-filament::dropdown placement="bottom-end" teleport>
                        <x-slot name="trigger">
                            <x-filament::icon-button icon="heroicon-m-ellipsis-vertical" size="sm" color="gray" label="Ещё" />
                        </x-slot>

                        <x-filament::dropdown.list>
                            <x-filament::dropdown.list.item
                                icon="heroicon-m-arrows-right-left"
                                x-on:click="close"
                                wire:click="mountAction('moveCategory', { category: {{ $row['id'] }} })"
                            >
                                Переместить…
                            </x-filament::dropdown.list.item>

                            @if ($row['direct'] > 0)
                                <x-filament::dropdown.list.item
                                    icon="heroicon-m-arrow-uturn-right"
                                    x-on:click="close"
                                wire:click="mountAction('transferProducts', { category: {{ $row['id'] }} })"
                                >
                                    Перенести товары…
                                </x-filament::dropdown.list.item>
                            @endif

                            <x-filament::dropdown.list.item
                                :icon="$row['active'] ? 'heroicon-m-eye-slash' : 'heroicon-m-eye'"
                                x-on:click="close"
                                wire:click="mountAction('toggleActive', { category: {{ $row['id'] }} })"
                            >
                                {{ $row['active'] ? 'Скрыть из каталога' : 'Показать в каталоге' }}
                            </x-filament::dropdown.list.item>

                            <x-filament::dropdown.list.item
                                icon="heroicon-m-trash"
                                color="danger"
                                x-on:click="close"
                                wire:click="mountAction('deleteCategory', { category: {{ $row['id'] }} })"
                            >
                                Удалить…
                            </x-filament::dropdown.list.item>
                        </x-filament::dropdown.list>
                    </x-filament::dropdown>
                </div>
            </div>
        @empty
            <div class="cat-row" style="justify-content: center; padding: 2rem;">
                <span class="cat-hint">
                    {{ $searching ? 'Ничего не найдено.' : 'Категорий пока нет. Нажмите «Добавить категорию».' }}
                </span>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
