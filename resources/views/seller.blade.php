@extends('main_layots')

@php
    $sellerName = trim($seller->name ?? 'Магазин', '" ');
    $initial = mb_strtoupper(mb_substr($sellerName, 0, 1));
    $location = $seller->settlement?->name ?: $seller->town;

    $telegramLink = $seller->telegram;
    if ($telegramLink && !str_starts_with($telegramLink, 'http://') && !str_starts_with($telegramLink, 'https://')) {
        $telegramLink = 'https://t.me/'.ltrim($telegramLink, '@');
    }

    $instagramLink = $seller->instagram;
    if ($instagramLink && !str_starts_with($instagramLink, 'http://') && !str_starts_with($instagramLink, 'https://')) {
        $instagramLink = 'https://instagram.com/'.ltrim($instagramLink, '@');
    }

    $viberLink = $seller->viber;
    if ($viberLink && !str_starts_with($viberLink, 'http://') && !str_starts_with($viberLink, 'https://') && !str_starts_with($viberLink, 'viber://')) {
        $viberLink = 'viber://chat?number='.preg_replace('/[^0-9+]/', '', $viberLink);
    }
@endphp

@section('title', $sellerName.' | Магазины ГРАНАТ')

@section('content')
<main class="market-page">
    <section class="seller-profile">
        <div class="container container-240">
            <ul class="market-breadcrumbs" aria-label="Хлебные крошки">
                <li><a href="{{ $regionalUrl->route('home') }}">Главная</a></li>
                <li><a href="{{ $regionalUrl->route('shops') }}">Магазины</a></li>
                <li aria-current="page">{{ $sellerName }}</li>
            </ul>

            <div class="seller-profile__panel">
                <div class="seller-profile__logo">
                    <div class="shop-logo">
                        @if($seller->photo)
                            <img src="{{ Storage::url($seller->photo) }}" alt="Логотип магазина {{ $sellerName }}"
                                width="380" height="380"
                                onerror="this.style.display='none';this.parentElement.textContent='{{ $initial }}';">
                        @else
                            {{ $initial }}
                        @endif
                    </div>
                </div>

                <div>
                    <div class="seller-profile__header">
                        <div>
                            <span class="market-eyebrow">Местный магазин</span>
                            <h1>{{ $sellerName }}</h1>
                        </div>
                        <span class="market-count">
                            {{ $products->total() }} {{ trans_choice('товар|товара|товаров', $products->total()) }}
                        </span>
                    </div>

                    @if($seller->description)
                        <p class="seller-profile__description">{{ $seller->description }}</p>
                    @endif

                    @if($seller->phone || $location || $seller->address)
                        <ul class="seller-contact-list">
                            @if($seller->phone)
                                <li>
                                    <i class="fa fa-phone" aria-hidden="true"></i>
                                    <span><a href="tel:{{ preg_replace('/[^0-9+]/', '', $seller->phone) }}">{{ $seller->phone }}</a></span>
                                </li>
                            @endif
                            @if($location)
                                <li>
                                    <i class="fa fa-map-marker" aria-hidden="true"></i>
                                    <span>{{ $location }}</span>
                                </li>
                            @endif
                            @if($seller->address)
                                <li>
                                    <i class="fa fa-location-arrow" aria-hidden="true"></i>
                                    <span>{{ $seller->address }}</span>
                                </li>
                            @endif
                        </ul>
                    @endif

                    @if($seller->phone || $seller->link_map || $seller->link || $telegramLink || $instagramLink || $viberLink)
                        <div class="seller-actions">
                            @if($seller->phone)
                                <a class="market-btn market-btn--primary" href="tel:{{ preg_replace('/[^0-9+]/', '', $seller->phone) }}">
                                    <i class="fa fa-phone" aria-hidden="true"></i> Позвонить
                                </a>
                            @endif
                            @if($seller->link_map)
                                <a class="market-btn market-btn--secondary" href="{{ $seller->link_map }}" target="_blank" rel="noopener">
                                    <i class="fa fa-map-marker" aria-hidden="true"></i> На карте
                                </a>
                            @endif
                            @if($seller->link)
                                <a class="market-btn market-btn--secondary" href="{{ $seller->link }}" target="_blank" rel="noopener">
                                    <i class="fa fa-external-link" aria-hidden="true"></i> Сайт
                                </a>
                            @endif
                            @if($telegramLink)
                                <a class="seller-social" href="{{ $telegramLink }}" target="_blank" rel="noopener" aria-label="Telegram">
                                    <i class="fab fa-telegram" aria-hidden="true"></i>
                                </a>
                            @endif
                            @if($instagramLink)
                                <a class="seller-social" href="{{ $instagramLink }}" target="_blank" rel="noopener" aria-label="Instagram">
                                    <i class="fab fa-instagram" aria-hidden="true"></i>
                                </a>
                            @endif
                            @if($viberLink)
                                <a class="seller-social" href="{{ $viberLink }}" aria-label="Viber">
                                    <i class="fab fa-viber" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="seller-products" aria-labelledby="seller-products-title">
        <div class="container container-240">
            <div class="market-section-heading">
                <div>
                    <span class="market-eyebrow">Ассортимент</span>
                    <h2 id="seller-products-title">Товары магазина</h2>
                    <p>Актуальные предложения продавца {{ $sellerName }}.</p>
                </div>
            </div>

            @if($products->isNotEmpty())
                <div class="home-products" id="seller-products-grid">
                    @foreach($products as $product)
                        @include('components.home-product-card', ['product' => $product])
                    @endforeach
                </div>

                @if($products->hasPages())
                    <div class="market-pagination pagination-desktop hidden-xs hidden-sm">
                        {{ $products->links('vendor.pagination.custom') }}
                    </div>

                    @if($products->currentPage() < $products->lastPage())
                        <div class="load-more-wrapper hidden-md hidden-lg text-center market-pagination">
                            <button class="market-btn market-btn--secondary btn-load-more"
                                data-url="{{ $regionalUrl->route('seller.loadMore', ['alias' => $seller->alias]) }}"
                                data-current-page="{{ $products->currentPage() }}"
                                data-last-page="{{ $products->lastPage() }}"
                                data-container="#seller-products-grid">
                                Загрузить ещё
                            </button>
                        </div>
                    @endif
                @endif
            @else
                <div class="market-empty">
                    <h2>Товаров пока нет</h2>
                    <p>Магазин ещё не добавил опубликованные товары.</p>
                </div>
            @endif
        </div>
    </section>
</main>
@endsection
