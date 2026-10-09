@extends('main_layots')

@section('title', 'BIMM — локальный маркетплейс')

@section('content')
<section class="bimm-hero bimm-hero--compact">
    <div class="bimm-hero__content">
        <span class="bimm-kicker">BIMM</span>
        <h1>{{ $seoHeading ?? 'Маркетплейс Приднестровья' }}</h1>
        <p>Заказывай товары с доставкой, узнавай, где купить и по какой цене, и находи контакты продавцов.</p>
    </div>
    <form class="bimm-search bimm-search--hero" action="{{ $regionalUrl->route('search') }}" method="GET" role="search">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <input type="search" name="q" placeholder="Что ищете? Например, наушники" autocomplete="off" aria-label="Поиск товаров">
        <button type="submit">Найти</button>
    </form>
</section>

@if($latestProducts->isNotEmpty())
    <section class="bimm-section bimm-section--latest">
        <div class="bimm-section__head"><div><span class="bimm-kicker">Свежие находки</span><h2>Новые предложения</h2></div><a class="bimm-section__link" href="{{ $regionalUrl->route('catalog.index') }}">Открыть каталог →</a></div>
        <div class="bimm-product-grid">@foreach($latestProducts as $product) @include('components.home-product-card', ['product' => $product]) @endforeach</div>
    </section>
@endif

@if($featuredCategories->isNotEmpty())
    <section class="bimm-section bimm-section--categories">
        <div class="bimm-section__head"><div><span class="bimm-kicker">Навигация</span><h2>Выберите направление</h2></div><a class="bimm-section__link" href="{{ $regionalUrl->route('catalog.index') }}">Весь каталог →</a></div>
        <div class="bimm-category-grid">
            @foreach($featuredCategories->take(10) as $category)
                @php
                    $categoryCount = (int) $category->visible_products_count;
                    $categoryCountLabel = ($categoryCount % 10 === 1 && $categoryCount % 100 !== 11)
                        ? 'предложение'
                        : (($categoryCount % 10 >= 2 && $categoryCount % 10 <= 4 && ($categoryCount % 100 < 10 || $categoryCount % 100 >= 20)) ? 'предложения' : 'предложений');
                @endphp
                <a class="bimm-category" href="{{ $regionalUrl->route('catalog.show', ['alias' => $category->alias]) }}">
                    <span class="bimm-category__icon">{{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}</span>
                    <span><strong>{{ $category->name }}</strong><small>{{ $categoryCount }} {{ $categoryCountLabel }}</small></span>
                </a>
            @endforeach
        </div>
    </section>
@endif

@if($saleProducts->isNotEmpty())
    <section class="bimm-section bimm-section--sale">
        <div class="bimm-section__head"><div><span class="bimm-kicker">Особая цена</span><h2>Распродажа дня</h2></div><a class="bimm-section__link" href="{{ $regionalUrl->route('catalog.sale') }}">Все скидки →</a></div>
        <div class="bimm-product-grid">@foreach($saleProducts as $product) @include('components.home-product-card', ['product' => $product]) @endforeach</div>
    </section>
@endif

@if($featuredSellers->isNotEmpty())
    <section class="bimm-section">
        <div class="bimm-section__head"><div><span class="bimm-kicker">Локальные бренды</span><h2>Магазины, которым доверяют</h2></div><a class="bimm-section__link" href="{{ $regionalUrl->route('shops') }}">Все магазины →</a></div>
        <div class="bimm-seller-grid">
            @foreach($featuredSellers as $seller)
                <a class="bimm-seller" href="{{ $regionalUrl->route('seller', ['alias' => $seller->alias]) }}">
                    <span class="bimm-seller__logo">@if($seller->photo)<img src="{{ asset('storage/'.ltrim($seller->photo, '/')) }}" alt="{{ $seller->name }}">@else{{ mb_strtoupper(mb_substr(trim($seller->name, '" '), 0, 1)) }}@endif</span>
                    <span><strong>{{ trim($seller->name, '" ') }}</strong><small>{{ $seller->town }} · {{ $seller->visible_products_count }} товаров</small></span>
                </a>
            @endforeach
        </div>
    </section>
@endif

@endsection
