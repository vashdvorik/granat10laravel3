@extends('main_layots')
@section('title', $q ? 'Поиск: '.$q.' — BIMM' : 'Поиск товаров — BIMM')

@section('content')

<div class="container container-240 search-page-container">
    <div id="catalog-top"></div>
    <div class="e-product">
        <ul class="breadcrumb v4">
            <li><a href="{{ route('home') }}">Главная</a></li>
            <li class="active">Поиск{{ $q ? ': ' . $q : '' }}</li>
        </ul>
        @include('components.region-notice', ['total' => $q ? $products->total() : null])

        <div class="pd-top">
            @if($q)
                <h1 class="title">
                    Результаты поиска: «{{ $q }}»
                    @if($selectedCategory)
                        <small>в категории «{{ $selectedCategory->name }}»</small>
                    @endif
                </h1>
                <div class="show-element">
                    <span>
                        @if($products->total() > 0)
                            Найдено {{ $products->total() }} {{ trans_choice('товар|товара|товаров', $products->total()) }}
                            @if($selectedCategory)
                                &nbsp;·&nbsp; <a href="{{ $regionalUrl->route('search', ['q' => $q]) }}">Искать во всех категориях</a>
                            @endif
                        @else
                            Ничего не найдено
                            @if($selectedCategory)
                                &nbsp;·&nbsp; <a href="{{ $regionalUrl->route('search', ['q' => $q]) }}">Искать во всех категориях</a>
                            @endif
                        @endif
                    </span>
                </div>
            @else
                <h1 class="title">Поиск товаров</h1>
            @endif
        </div>

        <form class="market-search search-page-form hidden-md hidden-lg" action="{{ $regionalUrl->route('search') }}" method="GET" role="search">
            <label class="sr-only" for="search-page-query">Поиск товаров</label>
            <i class="fa fa-search" aria-hidden="true"></i>
            <input id="search-page-query"
                   type="search"
                   name="q"
                   value="{{ $q }}"
                   placeholder="Что ищем?"
                   autocomplete="off">
            @if($categoryAlias)
                <input type="hidden" name="category" value="{{ $categoryAlias }}">
            @endif
            <button type="submit">Найти</button>
            @if(request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif
        </form>

        <div class="pd-middle search-mobile-filters hidden-md hidden-lg">
            <div class="pd-sort">
                @include('components.catalog-region-selector')
                @include('components.catalog-sort-dropdown')
            </div>
        </div>

        @if($products->count() > 0)
            <div class="product-collection-grid product-grid catalog-products-grid search-products-grid" id="search-products-grid">
                @foreach($products as $product)
                    @include('components.home-product-card', ['product' => $product])
                @endforeach
            </div>

            {{-- ПАГИНАЦИЯ --}}
            @if($products->hasPages())
                {{-- Desktop --}}
                <div class="pagination-desktop hidden-xs hidden-sm pd-middle space-v1">
                    {{ $products->links('vendor.pagination.custom') }}
                </div>

                {{-- Mobile: кнопка "Загрузить еще" --}}
                @if($products->currentPage() < $products->lastPage())
                    <div class="load-more-wrapper market-pagination hidden-md hidden-lg text-center">
                        <button class="market-btn market-btn--secondary btn-load-more"
                                data-url="{{ $regionalUrl->route('search.loadMore', ['q' => $q, 'category' => $categoryAlias, 'sort' => request('sort')]) }}"
                                data-current-page="{{ $products->currentPage() }}"
                                data-last-page="{{ $products->lastPage() }}"
                                data-container="#search-products-grid"
                                data-sort="{{ request('sort') }}">
                            <i class="ion-load-c"></i> Загрузить еще
                        </button>
                    </div>
                @endif
            @endif

        @elseif($q)
            <div class="search-empty text-center">
                <i class="ion-ios-search-strong search-empty-icon"></i>
                <p class="search-empty-text">По запросу <strong>«{{ $q }}»</strong> ничего не найдено.</p>
                <p>Попробуйте изменить запрос или перейдите в <a href="{{ $regionalUrl->route('catalog.index') }}">каталог</a>.</p>
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    .search-page-form.market-search {
        --market-search-action: #8750b4;
        align-items: center;
        background: #fff;
        border: 1px solid var(--market-border, #e8e8ed);
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(30, 30, 40, .07);
        box-sizing: border-box;
        column-gap: 8px;
        display: grid;
        grid-template-columns: 24px minmax(0, 1fr) 70px;
        margin: 0 0 24px;
        min-height: 62px;
        padding: 0 12px;
        width: 100%;
    }

    .search-page-form.market-search > i {
        align-items: center;
        color: var(--market-search-action);
        display: flex;
        font-size: 20px;
        justify-content: center;
    }

    .search-page-form.market-search input[type="search"] {
        background: transparent;
        border: 0;
        color: var(--market-text, #222228);
        font-size: 16px;
        height: 100%;
        min-width: 0;
        outline: 0;
        padding: 0;
        width: 100%;
    }

    .search-page-form.market-search input[type="search"]::placeholder {
        color: var(--market-muted, #747785);
        opacity: 1;
    }

    .search-page-form.market-search button {
        align-self: center;
        background: linear-gradient(90deg, #b965ed, #4ed9ee);
        border: 0;
        border-radius: 12px;
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        min-height: 44px;
        min-width: 0;
        padding: 0 12px;
        justify-self: stretch;
        width: 70px;
    }

    .search-page-form.market-search button:hover,
    .search-page-form.market-search button:focus {
        color: #fff;
        box-shadow: 0 8px 22px rgba(135, 80, 180, .22);
    }

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

    .search-products-grid {
        margin-top: 14px;
    }

    .search-mobile-filters {
        margin: -6px 0 18px;
    }

    .search-mobile-filters .pd-sort {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    @media (max-width: 767px) {
        .search-page-container.container-240 {
            box-sizing: border-box;
            margin: 0 !important;
            max-width: none !important;
            overflow-x: hidden;
            padding-left: 15px !important;
            padding-right: 15px !important;
            width: 100% !important;
        }

        .search-page-container .e-product {
            box-sizing: border-box;
            min-width: 0;
            overflow-x: hidden;
            width: 100%;
        }

        .search-page-form.market-search {
            grid-template-columns: 24px minmax(0, 1fr) 70px;
            margin: 12px 0 18px;
            max-width: 100% !important;
            min-height: 60px;
            overflow: hidden;
            padding: 0 11px;
            width: 100% !important;
        }

        .search-page-form.market-search input[type="search"] {
            font-size: 16px;
        }

        .search-page-form.market-search button {
            min-height: 44px;
            padding: 0 12px;
        }
    }
</style>
@endpush

@endsection
