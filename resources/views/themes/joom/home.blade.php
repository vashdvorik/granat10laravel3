@extends('main_layots')

@section('title', 'BIMM — маркетплейс Приднестровья')

@section('content')
@php
    $heroProducts = $latestProducts->take(3);
@endphp

<section class="joom-hero">
    <div class="joom-hero__copy">
        <span class="joom-eyebrow">BIMM · МАРКЕТПЛЕЙС ПРИДНЕСТРОВЬЯ</span>
        <h1>{{ $seoHeading ?? 'Покупки на каждый день' }}<br><em>рядом с вами</em></h1>
        <p>Заказывай товары с доставкой, сравнивай цены и находи контакты продавцов в своём городе.</p>
        <div class="joom-hero__buttons">
            <a class="joom-button joom-button--primary" href="{{ $regionalUrl->route('catalog.index') }}">Начать покупки <i class="fa-solid fa-arrow-right"></i></a>
            <a class="joom-button joom-button--quiet" href="{{ $regionalUrl->route('catalog.sale') }}">Смотреть скидки</a>
        </div>
    </div>
    <div class="joom-hero__showcase">
        <span class="joom-hero__deal">ВЫГОДНЫЕ ПРЕДЛОЖЕНИЯ</span>
        <strong class="joom-hero__deal-title">Товары рядом.<br>Цены понятные.</strong>
        <div class="joom-hero__products">
            @foreach($heroProducts as $heroProduct)
                @php
                    $heroImage = $heroProduct->images->where('is_main', true)->first() ?? $heroProduct->images->first();
                    $heroPrice = $heroProduct->lowprice && $heroProduct->lowprice < $heroProduct->price ? $heroProduct->lowprice : $heroProduct->price;
                @endphp
                <a class="joom-mini-product" href="{{ $regionalUrl->route('product.show', ['alias' => $heroProduct->alias]) }}">
                    <span class="joom-mini-product__image">
                        @if($heroImage)
                            <img src="{{ asset('storage/'.ltrim($heroImage->path, '/')) }}" alt="{{ $heroProduct->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('img/no-image.jpg') }}';">
                        @else
                            <span class="joom-image-placeholder"><i class="fa-regular fa-image" aria-hidden="true"></i></span>
                        @endif
                    </span>
                    <span class="joom-mini-product__price">{{ number_format((float) $heroPrice, 0, '.', ' ') }} ₽</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

@if(($popularSearchCategories ?? collect())->isNotEmpty())
    <div class="joom-chips" aria-label="Популярные запросы">
        <span>Ищут сейчас:</span>
        @foreach($popularSearchCategories->take(8) as $category)
            <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $category->alias]) }}">{{ $category->name }}</a>
        @endforeach
    </div>
@endif

@if($latestProducts->isNotEmpty())
    <section class="joom-section joom-section--products">
        <div class="joom-section__head">
            <div><span class="joom-eyebrow">ДЛЯ ВАС</span><h2>Новые товары</h2></div>
            <a href="{{ $regionalUrl->route('catalog.index') }}">Все товары <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="joom-product-row">@foreach($latestProducts as $product) @include('components.home-product-card', ['product' => $product]) @endforeach</div>
    </section>
@endif

<section class="joom-promo-row" aria-label="Преимущества BIMM">
    <a class="joom-promo-card joom-promo-card--coral" href="{{ $regionalUrl->route('catalog.sale') }}">
        <span>Скидки каждый день</span><strong>Находи свою цену</strong><i class="fa-solid fa-arrow-right"></i>
    </a>
    <a class="joom-promo-card joom-promo-card--blue" href="{{ $regionalUrl->route('shops') }}">
        <span>Магазины рядом</span><strong>Покупай у своих</strong><i class="fa-solid fa-arrow-right"></i>
    </a>
    <a class="joom-promo-card joom-promo-card--yellow" href="{{ $regionalUrl->route('help') }}">
        <span>Доставка по ПМР</span><strong>Уточняй детали напрямую</strong><i class="fa-solid fa-arrow-right"></i>
    </a>
</section>

@if($saleProducts->isNotEmpty())
    <section class="joom-section">
        <div class="joom-section__head">
            <div><span class="joom-eyebrow">УСПЕЙ ЗАБРАТЬ</span><h2>Предложения месяца</h2></div>
            <a href="{{ $regionalUrl->route('catalog.sale') }}">Все скидки <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="joom-product-row">@foreach($saleProducts as $product) @include('components.home-product-card', ['product' => $product]) @endforeach</div>
    </section>
@endif

@if($featuredCategories->isNotEmpty())
    <section class="joom-section joom-section--categories">
        <div class="joom-section__head"><div><span class="joom-eyebrow">ВЫБИРАЙ ЛЕГКО</span><h2>Категории</h2></div><a href="{{ $regionalUrl->route('catalog.index') }}">Все категории <i class="fa-solid fa-arrow-right"></i></a></div>
        <div class="joom-category-grid">
            @foreach($featuredCategories->take(10) as $category)
                <a class="joom-category" href="{{ $regionalUrl->route('catalog.show', ['alias' => $category->alias]) }}">
                    <span class="joom-category__icon">{{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}</span>
                    <strong>{{ $category->name }}</strong>
                    <small>{{ $category->visible_products_count }} товаров</small>
                </a>
            @endforeach
        </div>
    </section>
@endif

@if($featuredSellers->isNotEmpty())
    <section class="joom-section">
        <div class="joom-section__head"><div><span class="joom-eyebrow">МЕСТНЫЕ ПРОДАВЦЫ</span><h2>Магазины, которым доверяют</h2></div><a href="{{ $regionalUrl->route('shops') }}">Все магазины <i class="fa-solid fa-arrow-right"></i></a></div>
        <div class="joom-seller-row">
            @foreach($featuredSellers as $seller)
                <a class="joom-seller" href="{{ $regionalUrl->route('seller', ['alias' => $seller->alias]) }}">
                    <span class="joom-seller__logo">@if($seller->photo)<img src="{{ asset('storage/'.ltrim($seller->photo, '/')) }}" alt="{{ $seller->name }}">@else{{ mb_strtoupper(mb_substr(trim($seller->name, '" '), 0, 1)) }}@endif</span>
                    <strong>{{ trim($seller->name, '" ') }}</strong>
                    <small>{{ $seller->town }} · {{ $seller->visible_products_count }} товаров</small>
                </a>
            @endforeach
        </div>
    </section>
@endif
@endsection
