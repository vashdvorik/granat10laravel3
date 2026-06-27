@extends('main_layots')
@section('title', 'Распродажи | Маркетплейс ПМР')

@section('meta')
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
<div class="container container-240 sale-page">
    <div id="catalog-top"></div>
    <div class="e-product">
        <ul class="breadcrumb v4">
            <li><a href="{{ $regionalUrl->route('home') }}">Главная</a></li>
            <li class="active">Распродажи</li>
        </ul>

        <div class="pd-top sale-page__heading">
            <h1 class="title">Акции{{ $regionalContext->settlement() ? ' в '.$regionalContext->settlement()->name : ' в ПМР' }}</h1>
            @if($products->total() > 0)
                <div class="show-element">
                    <span>Показано {{ $products->firstItem() }}–{{ $products->lastItem() }} из {{ $products->total() }} товаров</span>
                </div>
            @endif
        </div>

        <div class="product-collection-grid product-grid sale-products-grid">
            @forelse($products as $product)
                @include('components.home-product-card', ['product' => $product])
            @empty
                <div class="sale-page__empty">
                    <h2>Сейчас нет товаров со скидкой</h2>
                    <p>Загляните позже или посмотрите общий каталог товаров.</p>
                    <a href="{{ $regionalUrl->route('catalog.index') }}" class="home-btn home-btn--primary">Перейти в каталог</a>
                </div>
            @endforelse
        </div>

        @if($products->hasPages())
            <div class="pagination-desktop hidden-xs hidden-sm pd-middle space-v1 sale-page__pagination">
                {{ $products->links('vendor.pagination.custom') }}
            </div>

            @if($products->currentPage() < $products->lastPage())
                <div class="load-more-wrapper market-pagination hidden-md hidden-lg text-center sale-page__load-more">
                    <button class="market-btn market-btn--secondary btn-load-more"
                            data-url="{{ $regionalUrl->route('catalog.loadMoreSale') }}"
                            data-current-page="{{ $products->currentPage() }}"
                            data-last-page="{{ $products->lastPage() }}">
                        <i class="ion-load-c"></i> Загрузить еще
                    </button>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
