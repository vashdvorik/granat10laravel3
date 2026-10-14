@extends('main_layots')
@section('title', 'Каталог товаров — BIMM')

@section('meta')
    {{-- SEO: Пагинация для поисковиков --}}
    @if(isset($products) && $products->hasPages())
        @if($products->previousPageUrl())
            <link rel="prev" href="{{ $products->previousPageUrl() }}" />
        @endif
        @if($products->nextPageUrl())
            <link rel="next" href="{{ $products->nextPageUrl() }}" />
        @endif
    @endif
@endsection

@section('content')
<div class="container container-240 shop-collection">
    <div id="catalog-top"></div>
    <ul class="breadcrumb">
        <li><a href="{{ route('home') }}">Главная</a></li>
        <li class="active"><a href="{{ $regionalUrl->route('catalog.index') }}">Каталог</a></li>

    <!--    <li class="active">{{ $category->name }}</li> -->
     @foreach($breadcrumbs as $crumb)
        @if ($loop->last)
            <li class="active">{{ $crumb->name }}</li>
        @else
            <li><a href="{{ $regionalUrl->route('catalog.show', ['alias' => $crumb->alias]) }}">{{ $crumb->name }}</a></li>
        @endif
    @endforeach

    </ul>
<!--<div class="filter-collection-left hidden-lg hidden-md">
      <a class="btn">Filter</a>
    </div>-->
    <div class="row shop-colect">  
    <!--СИТИБАР СЛЕВА  -->
        <div class="col-md-3 col-sm-3 col-xs-12 col-left collection-sidebar" id="filter-sidebar">
            <div class="close-sidebar-collection hidden-lg hidden-md">
                <span>filter</span><i class="icon_close ion-close"></i>
            </div>

            <!-- МЕНЮ-->
            <div class="filter filter-cate ">
                <ul class="wiget-content v2">
                    {{-- Кнопка "Назад" с умной логикой --}}
                    @php
                        // Используем ту же логику что и для мобильной версии
                        if ($hasChildren) {
                            // Есть дети - назад к родителю или каталог
                            $desktopBackUrl = $category->parent 
                                ? $regionalUrl->route('catalog.show', ['alias' => $category->parent->alias ?? $category->parent->id])
                                : $regionalUrl->route('catalog.index');
                            $backText = $category->parent ? 'Назад' : 'В каталог';
                        } else {
                            // Нет детей, показываются братья - назад на 2 уровня вверх
                            $desktopBackUrl = ($category->parent && $category->parent->parent) 
                                ? $regionalUrl->route('catalog.show', ['alias' => $category->parent->parent->alias ?? $category->parent->parent->id])
                                : $regionalUrl->route('catalog.index');
                            $backText = ($category->parent && $category->parent->parent) ? 'Назад' : 'В каталог';
                        }
                    @endphp
                    
                    <li class="back-category" style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #ddd;">
                        <a href="{{ $desktopBackUrl }}" style="display: flex; align-items: center; color: #666;">
                            <i class="ion-ios-arrow-left" style="margin-right: 8px;"></i>
                            <strong> {{ $backText }}</strong>
                        </a>
                    </li>
                    
                    @foreach ($categories as $subcategory)
                     <li class="{{ $subcategory->children->isNotEmpty() ? 'active' : '' }}">
                        <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $subcategory->alias]) }}">
                     {{--   {{ $subcategory->name }} <span class="number">(80)</span> --}}
                        {{ $subcategory->name }} <span class="number">({{ $categoryProductsCounts[$subcategory->id] ?? 0 }})</span>
                        </a>
                     </li>
                    @endforeach 
                </ul>
            </div>
            <!-- / МЕНЮ-->

            <!--РЕКЛАМНЫЙ БАННЕР 320*384-->
        {{--    <div class="banner">
                <a class="image-bd hover-images" href=""><img src="{{ asset('img/o-banner3.jpg') }}" alt="" class="img-reponsive"></a>
            </div>--}}  
        </div>
    <!--// СИТИБАР СЛЕВА  -->

    
        <div class="col-md-9 col-sm-12 col-xs-12 collection-list">
            <div class="e-product">
                <div class="pd-banner">
                   @php
                       $catalogBanner = app(\App\Services\CatalogBannerManager::class)->forDisplay();
                   @endphp
                   <a class="image-bd effect_img2" @if($catalogBanner['link']) href="{{ $catalogBanner['link'] }}" @endif><img src="{{ $catalogBanner['url'] }}" alt="" class="img-reponsive"></a>
                </div>
                <div class="pd-top">
                    <h1 class="title">{{ $seoHeading ?? $category->name }}</h1>
                    <div class="show-element">
                        <span>Показано {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} из {{ $products->total() }} товаров</span>
                    </div>
                </div>
                <div class="pd-middle">
                    <!--
                    <div class="view-mode view-group">
                        <a class="grid-icon col active"><img src="{{ asset('img/grid.png') }}" alt=""></a>
                        <a class="grid-icon col2"><img src="{{ asset('img/grid2.png') }}" alt=""></a>
                        <a class="list-icon list"><img src="{{ asset('img/list.png') }}" alt=""></a>
                    </div> -->
                    
                    <div class="pd-sort">
                        @include('components.catalog-region-selector')
                        {{--
                        <div class="filter-sort">
                            <div class="dropdown">
                              <button class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                    <span class="dropdown-label">Выберите район или рынок</span>
                              </button>
                              <ul class="dropdown-menu">
                                  <li><a href="manual">Featured</a></li>   
                                  <li><a href="best-selling">Best Selling</a></li>
                                  <li><a href="title-ascending">Alphabetically, A-Z</a></li>
                                    <li><a href="title-descending">Alphabetically, A-Z</a></li>
                                  <li><a href="price-descending">Price, high to low</a></li>
                                  <li><a href="price-ascending">Price, low to high</a></li>
                                  <li><a href="created-ascending">Date, old to new</a></li>
                                  <li><a href="created-descending">Date, new to old</a></li>
                              </ul>
                            </div>
                        </div>
                        --}}
                        @include('components.catalog-sort-dropdown')
                    </div>
                    
                </div>
<!-- МОБИЛЬНЫЕ КАРТОЧКИ КАТЕГОРИЙ /-->
<!-- Мобильная версия: карточки категорий 3x3 и плитки 3x3  -->
<div class="mobile-categories-wrapper hidden-md hidden-lg">
    @php
        // Проверяем, есть ли хотя бы одна иконка у категорий
        $hasIconsCheck = $categories->contains(function($cat) {
            return !empty($cat->icon);
        });
        
        // Определяем URL для кнопки "Назад"
        if ($hasChildren) {
            // Есть дети - назад к родителю или каталог
            $backUrl = $category->parent 
                ? $regionalUrl->route('catalog.show', ['alias' => $category->parent->alias ?? $category->parent->id])
                : $regionalUrl->route('catalog.index');
        } else {
            // Нет детей, показываются братья - назад на 2 уровня вверх
            $backUrl = ($category->parent && $category->parent->parent) 
                ? $regionalUrl->route('catalog.show', ['alias' => $category->parent->parent->alias ?? $category->parent->parent->id])
                : $regionalUrl->route('catalog.index');
        }
    @endphp

    @if($hasIconsCheck)
        {{-- РЕЖИМ С ИКОНКАМИ - карточки: не больше двух рядов, остальное листается вправо.
             Если вместе с «Назад» их не больше трёх, они стоят в один ряд на всю ширину. --}}
        <div class="mobile-categories-grid {{ $categories->count() + 1 <= 3 ? 'mobile-categories-grid--single' : '' }}">
            <!-- Кнопка "Назад" в виде карточки -->
            <div class="mobile-category-card back-card">
                <a href="{{ $backUrl }}">
                    <div class="mobile-category-image" 
                         style="background: linear-gradient(135deg, #e1f6fc 0%, #e7c4fc 100%);">
                        <i class="fas fa-arrow-left" style="color: #555555;"></i>
                    </div>
                    <div class="mobile-category-name">Назад</div>
                </a>
            </div>

            <!-- Список категорий с иконками -->
            @foreach ($categories as $subcategory)
                <div class="mobile-category-card">
                    <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $subcategory->alias ?? $subcategory->id]) }}">
                        <div class="mobile-category-image" 
                             style="background: {{ $subcategory->bg_color ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }};">
                            @if($subcategory->icon && str_starts_with($subcategory->icon, 'fa-'))
                                <i class="fas {{ $subcategory->icon }}" 
                                   style="color: {{ $subcategory->icon_color ?? '#ffffff' }};"></i>
                            @elseif($subcategory->icon)
                                <img src="{{ asset('storage/' . $subcategory->icon) }}" 
                                     alt="{{ $subcategory->name }}">
                            @else
                                <i class="fas fa-folder" 
                                   style="color: {{ $subcategory->icon_color ?? '#ffffff' }};"></i>
                            @endif
                        </div>
                        <div class="mobile-category-name">{{ $subcategory->name }}</div>
                    </a>
                </div>
            @endforeach
        </div>
    @else
        {{-- РЕЖИМ БЕЗ ИКОНОК - текстовые плитки: не больше двух рядов, остальное листается вправо.
             Плитки раскладываются по рядам по очереди (1-я сверху, 2-я снизу, 3-я сверху...), чтобы ряды были
             одной длины; «Назад» стоит первой плиткой первого ряда. Если категорий одна или две, они остаются
             в одном ряду вместе с «Назад». --}}
        @php
            $tileRows = $categories->count() > 2
                ? $categories->values()->partition(fn ($category, $index) => $index % 2 === 0)
                : collect([$categories->values()]);
        @endphp
        <div class="mobile-categories-list">
            <div class="mobile-categories-rows">
                @foreach ($tileRows as $tileRow)
                    <div class="mobile-categories-row">
                        @if ($loop->first)
                            <!-- Кнопка "Назад" в виде плитки: обычная плитка в начале первого ряда -->
                            <div class="mobile-category-tile back-tile">
                                <a href="{{ $backUrl }}">
                                    <i class="fas fa-arrow-left"></i>
                                    <span>Назад</span>
                                </a>
                            </div>
                        @endif
                        <!-- Список категорий текстом -->
                        @foreach ($tileRow as $subcategory)
                            <div class="mobile-category-tile">
                                <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $subcategory->alias ?? $subcategory->id]) }}">
                                    <span>{{ $subcategory->name }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<!-- Карточки подкатегорий -->
                <div class="product-collection-grid product-grid catalog-products-grid">
                    @forelse($products as $product)
                        @include('components.home-product-card', ['product' => $product])
                    @empty
                        <p>Нет товаров в этой категории.</p>
                    @endforelse
                </div>
                <div class="pd-middle space-v1">
                    @if($products->hasPages())
                        <!-- Пагинация для десктопа и SEO -->
                        <div class="pagination-desktop hidden-xs hidden-sm">
                            {{ $products->links('vendor.pagination.custom') }}
                        </div>
                        
                        <!-- Кнопка "Загрузить еще" для мобильных -->
                        <div class="load-more-wrapper market-pagination hidden-md hidden-lg text-center">
                            @if($products->currentPage() < $products->lastPage())
                                <button class="market-btn market-btn--secondary btn-load-more"
                                        data-url="{{ $regionalUrl->route('catalog.loadMoreCategory', ['alias' => $category->alias]) }}"
                                        data-current-page="{{ $products->currentPage() }}"
                                        data-last-page="{{ $products->lastPage() }}"
                                        data-seller="{{ request('seller') }}"
                                        data-sort="{{ request('sort') }}">
                                    <i class="ion-ios-reload"></i> Загрузить еще
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Автопрокрутка к началу каталога при пагинации
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли параметр page в URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('page')) {
        // Прокручиваем к началу каталога
        const catalogTop = document.getElementById('catalog-top');
        if (catalogTop) {
            catalogTop.scrollIntoView({ behavior: 'smooth', block: 'start' });
            // Небольшой отступ сверху (чтобы учесть header)
            setTimeout(() => {
                window.scrollBy(0, -100);
            }, 100);
        }
    }
});
</script>

@endsection
