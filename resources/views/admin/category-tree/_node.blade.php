@if ($categories->count())
    <ul class="cat-tree-list">
        @foreach ($categories as $category)
            <li class="cat-tree-item {{ $category->children->count() ? 'has-children' : '' }}">
                <div class="cat-tree-row" @if($category->children->count()) onclick="toggleNode(this)" @endif>

                    {{-- Стрелка --}}
                    @if ($category->children->count())
                        <span class="cat-toggle-icon">&#9660;</span>
                    @else
                        <span class="cat-toggle-icon cat-leaf"></span>
                    @endif

                    {{-- Иконка категории (если есть) --}}
                    @if ($category->icon)
                        <i class="{{ $category->icon }}"
                           style="color: {{ $category->icon_color ?: '#888' }}; margin-right: 6px;"></i>
                    @endif

                    {{-- Название --}}
                    <span class="cat-name">{{ $category->name }}</span>

                    {{-- Статус --}}
                    @if (!$category->is_active)
                        <span class="label label-default" style="margin-left: 8px; font-size: 10px;">скрыта</span>
                    @endif

                    {{-- Количество товаров в этой категории --}}
                    @if ($category->products_count > 0)
                        <span class="cat-badge">{{ $category->products_count }}</span>
                    @endif
                </div>

                {{-- Рекурсия --}}
                @if ($category->children->count())
                    <div class="cat-tree-children">
                        @include('admin.category-tree._node', ['categories' => $category->children])
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
@endif
