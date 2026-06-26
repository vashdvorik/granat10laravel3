@extends('main_layots')

@section('title', 'ГРАНАТ | Маркетплейс ПМР')

@section('content')
<main class="home-page">
    <section class="home-hero">
        <div class="container container-240">
            <div class="home-hero__panel home-hero__panel--search">
                <div class="home-hero__content">
                    <span class="home-eyebrow">Местные товары и магазины</span>
                    <h1>Товары и магазины рядом: {{ $locationName }}</h1>
                    <p>Товары местных магазинов, актуальные цены и прямой контакт с продавцом.</p>

                    <form class="home-search" action="{{ $regionalUrl->route('search') }}" method="GET" role="search">
                        <label class="sr-only" for="home-search-query">Поиск товаров</label>
                        <i class="fa fa-search" aria-hidden="true"></i>
                        <input id="home-search-query" type="search" name="q"
                            placeholder="Что вы ищете?" autocomplete="off">
                        <button type="submit">Найти</button>
                    </form>

                    @if($popularSearchCategories->isNotEmpty())
                        <div class="home-search-suggestions" aria-label="Часто ищут">
                            <span>Часто ищут:</span>
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
                        <span class="home-eyebrow">Быстрый выбор</span>
                        <h2 id="home-categories-title">Популярные категории</h2>
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
                        <span class="home-eyebrow">Свежие предложения</span>
                        <h2 id="home-latest-title">Новые товары</h2>
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
                        <span class="home-eyebrow">Цена ниже обычной</span>
                        <h2 id="home-sale-title">Выгодные предложения</h2>
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
                        <span class="home-eyebrow">Покупайте у местных</span>
                        <h2 id="home-sellers-title">Магазины рядом</h2>
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
                    <span class="home-eyebrow">Просто и понятно</span>
                    <h2 id="home-how-title">Как пользоваться маркетплейсом</h2>
                </div>
            </div>
            <div class="home-how__grid">
                <div><span>1</span><strong>Найдите товар</strong><p>Используйте поиск, категории и фильтры каталога.</p></div>
                <div><span>2</span><strong>Выберите продавца</strong><p>Посмотрите цену, описание, адрес и контакты магазина.</p></div>
                <div><span>3</span><strong>Свяжитесь или закажите</strong><p>Позвоните продавцу или отправьте заказ через сайт, если функция доступна.</p></div>
            </div>
        </div>
    </section>
</main>
@endsection
