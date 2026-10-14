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
        <li class="active">Каталог</li>
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
                    @foreach ($categories as $subcategory)
                     <li class="{{ $subcategory->children->isNotEmpty() ? 'active' : '' }}">
                        <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $subcategory->alias]) }}">
                        {{ $subcategory->name }}
                        <span class="number">
                            ({{ $categoryProductsCounts[$subcategory->id] ?? 0 }})
                        </span>
                        </a>
                     </li>
                    @endforeach 
                </ul>
            </div>
            <!-- / МЕНЮ-->
            <!--РЕКЛАМНЫЙ БАННЕР 320*384-->
    {{--       <div class="banner">
                <a class="image-bd hover-images" href=""><img src="{{ asset('img/o-banner3.jpg') }}" alt="" class="img-reponsive"></a>
            </div> --}} 
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
                    <h1 class="title">{{ $seoHeading ?? 'Каталог товаров' }}</h1>
                    <div class="show-element">
                        <span>Показано {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} из {{ $products->total() }} товаров</span>
                    </div>
                </div>
                <!-- Мобильная версия: карточки категорий, не больше двух рядов, остальное листается вправо -->
                <div class="mobile-categories-wrapper hidden-md hidden-lg">
                    <div class="mobile-categories-grid {{ $categories->count() <= 3 ? 'mobile-categories-grid--single' : '' }}">
                        @foreach ($categories as $subcategory)
                            <div class="mobile-category-card">
                                <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $subcategory->alias ?? $subcategory->id]) }}">
                                    <div class="mobile-category-image" 
                                        style="background: {{ $subcategory->bg_color ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }};">
                                        @if($subcategory->icon && str_starts_with($subcategory->icon, 'fa-'))
                                            <!-- Font Awesome иконка -->
                                            <i class="fas {{ $subcategory->icon }}" 
                                            style="color: {{ $subcategory->icon_color ?? '#ffffff' }};"></i>
                                        @elseif($subcategory->icon)
                                            <!-- Загруженное изображение -->
                                            <img src="{{ asset('storage/' . $subcategory->icon) }}" 
                                                alt="{{ $subcategory->name }}">
                                        @else
                                            <!-- Иконка по умолчанию -->
                                            <i class="fas fa-folder" 
                                            style="color: {{ $subcategory->icon_color ?? '#ffffff' }};"></i>
                                        @endif
                                    </div>
                                    <div class="mobile-category-name">{{ $subcategory->name }}</div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
                <!-- / МОБИЛЬНЫЕ КАРТОЧКИ КАТЕГОРИЙ -->

                <div class="pd-middle">
                    <div class="pd-sort">
                        @include('components.catalog-region-selector')
                        @include('components.catalog-sort-dropdown')
                    </div>
                </div>
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
                                        data-url="{{ $regionalUrl->route('catalog.loadMore') }}"
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
