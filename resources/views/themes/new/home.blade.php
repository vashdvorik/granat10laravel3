@extends('main_layots')

@section('title', 'BIMM — локальный маркетплейс')

@section('content')
<section class="bimm-hero">
    <div class="bimm-hero__content">
        <span class="bimm-kicker">Маркетплейс рядом</span>
        <h1>Находите вещи с характером.</h1>
        <p>Товары локальных продавцов, реальные магазины и честный выбор в одном красивом пространстве.</p>
        <form class="bimm-search bimm-search--hero" action="{{ $regionalUrl->route('search') }}" method="GET" role="search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="q" placeholder="Что ищете сегодня?" autocomplete="off">
            <button type="submit">Найти</button>
        </form>
    </div>
</section>

@if($featuredCategories->isNotEmpty())
    <section class="bimm-section">
        <div class="bimm-section__head"><div><span class="bimm-kicker">Навигация</span><h2>Выберите направление</h2></div><a class="bimm-section__link" href="{{ $regionalUrl->route('catalog.index') }}">Весь каталог →</a></div>
        <div class="bimm-category-grid">
            @foreach($featuredCategories->take(10) as $category)
                <a class="bimm-category" href="{{ $regionalUrl->route('catalog.show', ['alias' => $category->alias]) }}">
                    <span class="bimm-category__icon">{{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}</span>
                    <span><strong>{{ $category->name }}</strong><small>{{ $category->visible_products_count }} предложений</small></span>
                </a>
            @endforeach
        </div>
    </section>
@endif

@if($latestProducts->isNotEmpty())
    <section class="bimm-section">
        <div class="bimm-section__head"><div><span class="bimm-kicker">Свежие находки</span><h2>Новые предложения</h2></div><a class="bimm-section__link" href="{{ $regionalUrl->route('catalog.index') }}">Открыть каталог →</a></div>
        <div class="bimm-product-grid">@foreach($latestProducts as $product) @include('components.home-product-card', ['product' => $product]) @endforeach</div>
    </section>
@endif

@if($saleProducts->isNotEmpty())
    <section class="bimm-section">
        <div class="bimm-section__head"><div><span class="bimm-kicker">Особая цена</span><h2>Выгодные предложения</h2></div><a class="bimm-section__link" href="{{ $regionalUrl->route('catalog.sale') }}">Все скидки →</a></div>
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

<section class="bimm-section">
    <div class="bimm-section__head"><div><span class="bimm-kicker">Как это работает</span><h2>Покупать локальное — просто</h2></div></div>
    <div class="bimm-how">
        <article><span>01</span><h3>Найдите нужное</h3><p>Ищите товар или открывайте категории с предложениями продавцов вашего региона.</p></article>
        <article><span>02</span><h3>Выберите магазин</h3><p>Сравните условия, город, описание и контакты в одной карточке товара.</p></article>
        <article><span>03</span><h3>Свяжитесь напрямую</h3><p>Уточните наличие, доставку и оформите заказ удобным способом.</p></article>
    </div>
</section>
@endsection
