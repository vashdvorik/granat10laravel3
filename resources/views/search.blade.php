@extends('main_layots')
@section('title', $q ? 'Поиск: ' . $q . ' | Маркетплейс ПМР' : 'Поиск товаров | Маркетплейс ПМР')

@section('content')

<div class="container container-240">
    <div id="catalog-top"></div>
    <div class="e-product">
        <ul class="breadcrumb v4">
            <li><a href="{{ route('home') }}">Главная</a></li>
            <li class="active">Поиск{{ $q ? ': ' . $q : '' }}</li>
        </ul>

        <div class="pd-top">
            @if($q)
                <h1 class="title">Результаты поиска: «{{ $q }}»</h1>
                <div class="show-element">
                    <span>
                        @if($products->total() > 0)
                            Найдено {{ $products->total() }} {{ trans_choice('товар|товара|товаров', $products->total()) }}
                        @else
                            Ничего не найдено
                        @endif
                    </span>
                </div>
            @else
                <h1 class="title">Поиск товаров</h1>
            @endif
        </div>

        @if($products->count() > 0)
            <div class="product-collection-grid product-grid spc1">
                <div class="row" id="search-products-grid">
                    @foreach($products as $product)
                        @include('components.productcategories', ['product' => $product])
                    @endforeach
                </div>
            </div>

            {{-- ПАГИНАЦИЯ --}}
            @if($products->hasPages())
                {{-- Desktop --}}
                <div class="pagination-desktop hidden-xs hidden-sm pd-middle space-v1">
                    {{ $products->links('vendor.pagination.custom') }}
                </div>

                {{-- Mobile: кнопка "Загрузить еще" --}}
                @if($products->currentPage() < $products->lastPage())
                    <div class="load-more-wrapper hidden-md hidden-lg text-center">
                        <button class="btn btn-load-more"
                                data-url="{{ route('search.loadMore') }}?q={{ urlencode($q) }}"
                                data-current-page="{{ $products->currentPage() }}"
                                data-last-page="{{ $products->lastPage() }}"
                                data-container="#search-products-grid">
                            <i class="ion-load-c"></i> Загрузить еще
                        </button>
                    </div>
                @endif
            @endif

        @elseif($q)
            <div class="search-empty text-center">
                <i class="ion-ios-search-strong search-empty-icon"></i>
                <p class="search-empty-text">По запросу <strong>«{{ $q }}»</strong> ничего не найдено.</p>
                <p>Попробуйте изменить запрос или перейдите в <a href="{{ route('catalog.index') }}">каталог</a>.</p>
            </div>
        @endif
    </div>
</div>

<style>
    .search-empty {
        padding: 60px 20px;
        color: #888;
    }

    .search-empty-icon {
        font-size: 64px;
        color: #ddd;
        display: block;
        margin-bottom: 20px;
    }

    .search-empty-text {
        font-size: 18px;
        margin-bottom: 10px;
    }
</style>

@endsection
