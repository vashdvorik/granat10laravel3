@extends('main_layots')

@section('title', 'Где купить в Приднестровье — BIMM')

@section('content')
<main class="home-page">
    {{-- Город применяется только по адресу: здесь либо плашка «показан один город», либо подсказка «в прошлый раз вы выбирали...» --}}
    @if($regionalContext->settlement() || $regionalContext->suggestedSettlement())
        <div class="container container-240 home-region-strip">
            @include('components.region-notice')
            @include('components.region-suggestion')
        </div>
    @endif
    <section class="home-hero">
        <div class="container container-240">
            <div class="home-hero__panel home-hero__panel--search">
                {{-- На компьютере две колонки: слева заголовок и описание, справа поиск с подсказками. На телефоне идут друг под другом. --}}
                <div class="home-hero__content">
                    <div class="home-hero__text">
                        <span class="home-eyebrow">Где купить? Смотри на BIMM.</span>
                        <h1>{{ $seoHeading ?? 'Маркетплейс Приднестровья' }}</h1>
                        <p>BIMM собирает товары продавцов из Приднестровья в одном месте. Ищите товар, смотрите, где он есть, уточняйте наличие и оформляйте заказ напрямую.</p>
                    </div>

                    <div class="home-hero__finder">
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
        </div>
    </section>

    {{-- Призыв для будущих продавцов сразу после первого блока. Тем, у кого уже есть магазин, он не нужен. --}}
    @php
        $becomeSellerUser = auth()->user();
        $showBecomeSeller = ! $becomeSellerUser || ! $becomeSellerUser->seller;
    @endphp
    @if($showBecomeSeller)
        <section class="home-become-seller" aria-labelledby="home-become-seller-title">
            <div class="container container-240">
                <div class="home-become-seller__panel">
                    <div class="home-become-seller__text">
                        <h2 id="home-become-seller-title">Хочешь стать продавцом?</h2>
                        <p>Размести свои товары на BIMM: покупатели увидят цену и контакты и свяжутся с тобой напрямую.</p>
                    </div>
                    <div class="home-become-seller__actions">
                        @if($becomeSellerUser)
                            <a class="home-btn home-btn--seller" href="{{ route('account.store.create') }}">Открыть магазин</a>
                        @else
                            <a class="home-btn home-btn--seller" href="{{ route('register') }}">Стать продавцом</a>
                        @endif
                        <a class="home-btn home-btn--ghost" href="{{ route('seller.help') }}">Как это работает</a>
                    </div>
                    @guest
                        <p class="home-become-seller__login">Уже продаёте? <a href="{{ route('login') }}">Войти в кабинет</a></p>
                    @endguest
                </div>
            </div>
        </section>
    @endif

    {{-- Возвращающимся посетителям: что они смотрели и сколько у них в избранном. Всё берётся из памяти браузера
         (public/js/home-feed.js), поэтому у новичка блока нет вообще. --}}
    <section class="home-section home-returns" id="home-returns" hidden
        data-products-url="{{ $regionalUrl->route('favorites.products') }}"
        data-favorites-url="{{ $regionalUrl->route('favorites') }}">
        <div class="container container-240">
            <a class="home-returns__favorites" href="{{ $regionalUrl->route('favorites') }}" hidden>
                <i class="fa fa-heart" aria-hidden="true"></i>
                <span class="home-returns__favorites-text"></span>
                <span class="home-returns__favorites-open">Открыть</span>
            </a>
            <div class="home-returns__viewed" hidden>
                <div class="home-section__heading">
                    <div>
                        <h2>Вы смотрели</h2>
                    </div>
                </div>
                <div class="home-products home-returns__list"></div>
            </div>
        </div>
    </section>

    {{-- Категории плитками с картинками (как на странице «Каталог»): корневые категории, где сейчас есть товары.
         Цифры «2 товара» убраны: у молодой площадки маленькие числа только отпугивают. На телефоне 4 в ряд, помещается всё,
         листать не нужно; последней идёт плитка «Все категории». --}}
    @if($feedChips->isNotEmpty())
        <section class="home-section home-categories" aria-labelledby="home-categories-title">
            <div class="container container-240">
                <div class="home-section__heading">
                    <div>
                        <h2 id="home-categories-title">Категории товаров</h2>
                    </div>
                </div>

                <div class="home-cats">
                    @foreach($feedChips->take(7) as $cat)
                        @php
                            // поля картинки и цветов могут отсутствовать в записи, сохранённой в кеше до обновления сайта
                            $catIcon = $cat['icon'] ?? null;
                            $catBg = $cat['bg_color'] ?? null;
                            $catColor = $cat['icon_color'] ?? null;
                        @endphp
                        <a class="home-cat" href="{{ $regionalUrl->route('catalog.show', ['alias' => $cat['alias']]) }}">
                            <span class="home-cat__img" @if($catBg) style="background: {{ $catBg }};" @endif aria-hidden="true">
                                @if($catIcon && str_starts_with($catIcon, 'fa-'))
                                    <i class="fas {{ $catIcon }}" style="color: {{ $catColor ?: '#9152c7' }};"></i>
                                @elseif($catIcon)
                                    <img src="{{ asset('storage/'.$catIcon) }}" alt="" width="112" height="112" loading="lazy">
                                @else
                                    {{ mb_strtoupper(mb_substr(trim($cat['name']), 0, 1)) }}
                                @endif
                            </span>
                            <span class="home-cat__name">{{ $cat['name'] }}</span>
                        </a>
                    @endforeach
                    <a class="home-cat home-cat--all" href="{{ $regionalUrl->route('catalog.index') }}">
                        <span class="home-cat__img" aria-hidden="true"><i class="fa fa-th-large"></i></span>
                        <span class="home-cat__name">Все категории</span>
                    </a>
                </div>
            </div>
        </section>
    @endif
    @if($latestProducts->isNotEmpty())
        <section class="home-section home-latest" aria-labelledby="home-latest-title">
            <div class="container container-240">
                {{-- Город берётся из адреса (/tiraspol): «Новинки в Тирасполе»; на общем адресе — «Новинки в Приднестровье» --}}
                <div class="home-section__heading">
                    <div>
                        <h2 id="home-latest-title">Новинки {{ $regionalContext->settlement()?->inPhrase() ?? 'в Приднестровье' }}</h2>
                    </div>
                </div>

                {{-- Быстрые фильтры: корневые категории, где сейчас есть товары. Кнопки подгружают карточки без перезагрузки
                     страницы (public/js/home-feed.js), листание ленты вбок остаётся. --}}
                @if($feedChips->count() > 1)
                    <div class="home-chips" data-feed-url="{{ $regionalUrl->route('home.latest') }}" role="group" aria-label="Категории новинок">
                        <button type="button" class="home-chip is-active" data-category="">Все</button>
                        @foreach($feedChips as $chip)
                            <button type="button" class="home-chip" data-category="{{ $chip['alias'] }}">{{ $chip['name'] }}</button>
                        @endforeach
                    </div>
                @endif

                <div class="home-products" id="home-latest-feed" aria-live="polite">
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
        <section class="home-section home-shops-section" aria-labelledby="home-sellers-title">
            <div class="container container-240">
                {{-- Магазины лентой круглых логотипов: на экране телефона видно 4-5 штук, последним идёт «Все магазины».
                     Заголовок называет регион так же, как у новинок: «Магазины Приднестровья» / «Магазины в Тирасполе». --}}
                <div class="home-section__heading">
                    <div>
                        <h2 id="home-sellers-title">Магазины {{ $regionalContext->settlement()?->inPhrase() ?? 'Приднестровья' }}</h2>
                    </div>
                </div>

                <div class="home-shops">
                    @foreach($featuredSellers as $seller)
                        <a class="home-shop" href="{{ $regionalUrl->route('seller', ['alias' => $seller->alias]) }}">
                            <span class="home-shop__logo">
                                @if($seller->photo)
                                    <img src="{{ Storage::url($seller->photo) }}" alt="" width="88" height="88" loading="lazy">
                                @else
                                    {{ mb_strtoupper(mb_substr(trim($seller->name, '" '), 0, 1)) }}
                                @endif
                            </span>
                            <strong class="home-shop__name">{{ trim($seller->name, '" ') }}</strong>
                            <small class="home-shop__meta">{{ $seller->visible_products_count }} {{ trans_choice('товар|товара|товаров', $seller->visible_products_count) }}</small>
                        </a>
                    @endforeach
                    <a class="home-shop home-shop--all" href="{{ $regionalUrl->route('shops') }}">
                        <span class="home-shop__logo"><i class="fa fa-arrow-right" aria-hidden="true"></i></span>
                        <strong class="home-shop__name">Все магазины</strong>
                        <small class="home-shop__meta">{{ $visibleSellersCount }} {{ trans_choice('магазин|магазина|магазинов', $visibleSellersCount) }}</small>
                    </a>
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
