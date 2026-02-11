@foreach($items as $category)
    @php
        // Проверяем есть ли товары в этой категории
        $hasProducts = in_array($category->id, $sellerCategoryIds);
        $productCount = $hasProducts ? $seller->products()->where('category_id', $category->id)->count() : 0;
        
        // Показываем если есть товары
        $shouldShow = $hasProducts || $category->children->count() > 0;
    @endphp
    
    @if($shouldShow)
        <li style="margin-left: {{ ($level - 1) * 15 }}px;">
            {{-- Если есть товары - делаем ссылкой --}}
            @if($hasProducts)
                <a href="{{ route('account.store.products.index', ['category' => $category->id]) }}" 
                   class="d-flex justify-content-between align-items-center category-link {{ request('category') == $category->id ? 'active' : '' }}">
                    <span class="category-name">{{ $category->name }}</span>
                    <span class="item-numbers">{{ $productCount }}</span>
                </a>
            @else
                <span class="category-parent">{{ $category->name }}</span>
            @endif
            
            {{-- Рекурсивно отображаем дочерние категории --}}
            @if($category->children && $category->children->count())
                <ul class="subcategories">
                    @include('components.category-filter-tree', [
                        'items' => $category->children,
                        'level' => $level + 1,
                        'seller' => $seller,
                        'sellerCategoryIds' => $sellerCategoryIds
                    ])
                </ul>
            @endif
        </li>
    @endif
@endforeach