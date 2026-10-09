@extends('main_layots')

@section('title', 'Магазины'.($regionalContext->settlement() ? ' в '.$regionalContext->settlement()->name : ' ПМР').' — BIMM')

@section('meta')
    @if($sellers->hasPages())
        @if($sellers->previousPageUrl())
            <link rel="prev" href="{{ $sellers->previousPageUrl() }}">
        @endif
        @if($sellers->nextPageUrl())
            <link rel="next" href="{{ $sellers->nextPageUrl() }}">
        @endif
    @endif
@endsection

@section('content')
<main class="market-page">
    <section class="shops-hero">
        <div class="container container-240">
            <ul class="market-breadcrumbs" aria-label="Хлебные крошки">
                <li><a href="{{ $regionalUrl->route('home') }}">Главная</a></li>
                <li aria-current="page">Магазины</li>
            </ul>

            <div class="shops-hero__panel">
                <div>
                    <span class="market-eyebrow">Выбирай свой город</span>
                    <h1>{{ $seoHeading ?? 'Магазины' }}</h1>
                    <p>Смотрите ассортимент, адреса и контакты местных продавцов в одном месте.</p>
                    <div class="shops-hero__region">
                        @include('components.regional-selector', ['variant' => 'hero'])
                    </div>
                </div>
                <div class="shops-hero__count">
                    <strong>{{ $sellers->total() }}</strong>
                    <span>{{ trans_choice('магазин|магазина|магазинов', $sellers->total()) }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="shops-section" aria-label="Магазины">
        <div class="container container-240">
            @if($sellers->isNotEmpty())
                <div class="shops-grid" id="shops-grid">
                    @foreach($sellers as $seller)
                        @include('components.seller-card', ['seller' => $seller])
                    @endforeach
                </div>

                @if($sellers->hasPages())
                    <div class="market-pagination pagination-desktop hidden-xs hidden-sm">
                        {{ $sellers->links('vendor.pagination.custom') }}
                    </div>

                    @if($sellers->currentPage() < $sellers->lastPage())
                        <div class="load-more-wrapper hidden-md hidden-lg text-center market-pagination">
                            <button class="market-btn market-btn--secondary btn-load-more"
                                data-url="{{ $regionalUrl->route('shops.loadMore') }}"
                                data-current-page="{{ $sellers->currentPage() }}"
                                data-last-page="{{ $sellers->lastPage() }}"
                                data-container="#shops-grid">
                                Загрузить ещё
                            </button>
                        </div>
                    @endif
                @endif
            @else
                <div class="market-empty">
                    <h2>Магазинов пока нет</h2>
                    <p>Попробуйте выбрать другой город или вернуться позже.</p>
                </div>
            @endif
        </div>
    </section>
</main>
@endsection
