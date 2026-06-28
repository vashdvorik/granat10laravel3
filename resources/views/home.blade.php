@extends('main_layots')

@section('title', 'BIMM | Где купить в Приднестровье')

@section('content')
<main class="home-page">
    <section class="home-hero">
        <div class="container container-240">
            <div class="home-hero__panel home-hero__panel--search">
                <div class="home-hero__content">
                    <span class="home-eyebrow">Все товары Приднестровья в одном месте</span>
                    <h1>Где купить? Смотри на BIMM.</h1>
                    <p>BIMM собирает товары продавцов из Приднестровья в одном месте. Ищите товар, смотрите, где он есть, уточняйте наличие и оформляйте заказ напрямую.</p>

                    <form class="home-search" action="{{ $regionalUrl->route('search') }}" method="GET" role="search">
                        <label class="sr-only" for="home-search-query">Поиск товаров</label>
                        <i class="fa fa-search" aria-hidden="true"></i>
                        <input id="home-search-query" type="search" name="q"
                            placeholder="Какой товар ищете?" autocomplete="off">
                        <button type="submit">Найти</button>
                    </form>

                    @if($popularSearchCategories->isNotEmpty())
                        <div class="home-search-suggestions" aria-label="Часто ищут">
                        <span>Не ищи по чатам. Смотри:</span>
                            @foreach($popularSearchCategories as $category)
                                <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $category->alias]) }}">
                                    {{ $category->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if($featuredCategories->isNotEmpty())
        <section class="home-section home-categories" aria-labelledby="home-categories-title">
            <div class="container container-240">
                <div class="home-section__heading">
                    <div>
                        <span class="home-eyebrow">Начните с категории</span>
                        <h2 id="home-categories-title">Товары, которые уже есть на BIMM</h2>
                    </div>
                    <a href="{{ $regionalUrl->route('catalog.index') }}">Все категории</a>
                </div>

                <div class="home-categories__grid">
                    @foreach($featuredCategories as $category)
                        <a class="home-category" href="{{ $regionalUrl->route('catalog.show', ['alias' => $category->alias]) }}">
                            <span class="home-category__icon" aria-hidden="true">
                                {{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}
                            </span>
                            <span class="home-category__content">
                                <strong>{{ $category->name }}</strong>
                                <small>{{ $category->visible_products_count }}
                                    {{ trans_choice('товар|товара|товаров', $category->visible_products_count) }}</small>
                            </span>
                            <i class="fa fa-angle-right" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($latestProducts->isNotEmpty())
        <section class="home-section" aria-labelledby="home-latest-title">
            <div class="container container-240">
                <div class="home-section__heading">
                    <div>
                        <span class="home-eyebrow">Появилось недавно</span>
                        <h2 id="home-latest-title">Новые товары продавцов</h2>
                    </div>
                    <a href="{{ $regionalUrl->route('catalog.index') }}">Смотреть каталог</a>
                </div>

                <div class="home-products">
                    @foreach($latestProducts as $product)
                        @include('components.home-product-card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($saleProducts->isNotEmpty())
        <section class="home-section home-sale" aria-labelledby="home-sale-title">
            <div class="container container-240">
                <div class="home-section__heading">
                    <div>
                        <span class="home-eyebrow">Можно купить выгоднее</span>
                        <h2 id="home-sale-title">Товары со скидкой</h2>
                    </div>
                    <a href="{{ $regionalUrl->route('catalog.sale') }}">Все скидки</a>
                </div>

                <div class="home-products home-products--sale">
                    @foreach($saleProducts as $product)
                        @include('components.home-product-card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($featuredSellers->isNotEmpty())
        <section class="home-section" aria-labelledby="home-sellers-title">
            <div class="container container-240">
                <div class="home-section__heading">
                    <div>
                        <span class="home-eyebrow">Ваш товар уже ищут</span>
                        <h2 id="home-sellers-title">Продавцы на BIMM</h2>
                    </div>
                    <a href="{{ $regionalUrl->route('shops') }}">Все магазины</a>
                </div>

                <div class="home-sellers">
                    @foreach($featuredSellers as $seller)
                        <a class="home-seller" href="{{ $regionalUrl->route('seller', ['alias' => $seller->alias]) }}">
                            <span class="home-seller__logo">
                                @if($seller->photo)
                                    <img src="{{ Storage::url($seller->photo) }}" alt="{{ $seller->name }}"
                                        width="72" height="72" loading="lazy">
                                @else
                                    {{ mb_strtoupper(mb_substr(trim($seller->name, '" '), 0, 1)) }}
                                @endif
                            </span>
                            <span class="home-seller__content">
                                <strong>{{ trim($seller->name, '" ') }}</strong>
                                <small>{{ $seller->town }} · {{ $seller->visible_products_count }}
                                    {{ trans_choice('товар|товара|товаров', $seller->visible_products_count) }}</small>
                            </span>
                            <i class="fa fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="home-section home-how" aria-labelledby="home-how-title">
        <div class="container container-240">
            <div class="home-section__heading">
                <div>
                    <span class="home-eyebrow">Всё, что продаётся рядом, должно быть видно</span>
                    <h2 id="home-how-title">Как BIMM помогает купить</h2>
                </div>
            </div>
            <div class="home-how__grid">
                <div><span>1</span><strong>Ищете товар</strong><p>Введите запрос или откройте категорию — BIMM покажет товары, которые есть у продавцов.</p></div>
                <div><span>2</span><strong>Смотрите, где есть</strong><p>Сравните цену, описание, город, магазин и контакты продавца в одной карточке.</p></div>
                <div><span>3</span><strong>Уточняете и покупаете</strong><p>Свяжитесь с продавцом, уточните наличие и оформите заказ удобным способом.</p></div>
            </div>
        </div>
    </section>
</main>
@endsection
