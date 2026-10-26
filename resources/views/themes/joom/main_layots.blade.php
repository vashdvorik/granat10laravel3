<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle ?: (trim($__env->yieldContent('title')) ?: \App\Support\PageTitle::brand()) }}</title>
    @include('partials.seo.head')
    <link rel="icon" href="{{ asset('img/favicon.png') }}?v={{ filemtime(public_path('img/favicon.png')) }}" type="image/png">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="@assetv('css/theme-joom.css')">
    @stack('head')
    @stack('styles')
</head>
<body class="theme-joom">
    <div class="joom-topbar">
        <div class="joom-container joom-topbar__inner">
            <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Маркетплейс Приднестровья</span>
            <nav aria-label="Сервисная навигация">
                <a href="{{ $regionalUrl->route('help') }}">Поддержка</a>
                <a href="{{ $regionalUrl->route('help') }}">Доставка</a>
                <a href="{{ $regionalUrl->route('gobuyer') }}">Продавцам</a>
            </nav>
            <span class="joom-topbar__locale">ПМР · ₽ <i class="fa-solid fa-chevron-down" aria-hidden="true"></i></span>
        </div>
    </div>

    <header class="joom-header">
        <div class="joom-container joom-header__row">
            <a class="joom-brand" href="{{ $regionalUrl->route('home') }}" aria-label="BIMM — маркетплейс Приднестровья">
                <img class="joom-brand__logo" src="{{ asset('img/logo.png') }}" alt="BIMM — маркетплейс Приднестровья">
            </a>

            <form class="joom-search" action="{{ $regionalUrl->route('search') }}" method="GET" role="search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Найти товар или магазин" aria-label="Найти товар или магазин">
                <button type="submit" aria-label="Искать"><i class="fa-solid fa-arrow-right"></i></button>
            </form>

            <div class="joom-header__actions">
                <a class="joom-header__action" href="{{ $regionalUrl->route('favorites') }}" aria-label="Избранное"><i class="fa-regular fa-heart"></i><span>Избранное</span></a>
                <a class="joom-header__action" href="{{ $regionalUrl->route('orders.index') }}" aria-label="Заказы"><i class="fa-solid fa-bag-shopping"></i><span>Заказы</span></a>
                <button class="joom-menu-toggle" type="button" data-joom-menu-toggle aria-expanded="false" aria-label="Открыть меню"><i class="fa-solid fa-bars"></i></button>
            </div>
        </div>

        <div class="joom-category-bar">
            <div class="joom-container joom-category-bar__inner">
                <button class="joom-category-all" type="button" data-joom-categories-toggle aria-expanded="false"><i class="fa-solid fa-grid-2" aria-hidden="true"></i> Все категории <i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button>
                <nav class="joom-category-nav" aria-label="Популярные категории">
                    @php($navCategories = $popularSearchCategories ?? ($categories ?? collect()))
                    @foreach($navCategories->take(11) as $category)
                        <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $category->alias]) }}">{{ $category->name }}</a>
                    @endforeach
                    <a href="{{ $regionalUrl->route('catalog.index') }}">Ещё <i class="fa-solid fa-chevron-down" aria-hidden="true"></i></a>
                </nav>
            </div>
        </div>

        <nav class="joom-mobile-menu" data-joom-mobile-menu aria-label="Мобильная навигация">
            <form class="joom-search" action="{{ $regionalUrl->route('search') }}" method="GET" role="search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" name="q" placeholder="Найти товар или магазин" aria-label="Найти товар или магазин">
                <button type="submit" aria-label="Искать"><i class="fa-solid fa-arrow-right"></i></button>
            </form>
            <a href="{{ $regionalUrl->route('catalog.index') }}"><i class="fa-solid fa-grid-2"></i> Все категории</a>
            <a href="{{ $regionalUrl->route('catalog.sale') }}"><i class="fa-solid fa-bolt"></i> Скидки</a>
            <a href="{{ $regionalUrl->route('shops') }}"><i class="fa-solid fa-store"></i> Магазины</a>
            <a href="{{ $regionalUrl->route('gobuyer') }}"><i class="fa-solid fa-shop"></i> Продавцам</a>
        </nav>
    </header>

    <main class="joom-main">
        <div class="joom-container">
            @if(session('success'))<div class="joom-flash">{{ session('success') }}</div>@endif
            @if(session('status'))<div class="joom-flash">{{ session('status') }}</div>@endif
            @yield('content')
        </div>
    </main>

    <footer class="joom-footer">
        <div class="joom-container">
            <div class="joom-footer__grid">
                <div>
                    <a class="joom-brand" href="{{ $regionalUrl->route('home') }}" aria-label="BIMM — маркетплейс Приднестровья"><img class="joom-brand__logo" src="{{ asset('img/logo.png') }}" alt="BIMM — маркетплейс Приднестровья"></a>
                    <p>Товары местных продавцов с понятной ценой и доставкой.</p>
                </div>
                <div><strong>Покупателям</strong><a href="{{ $regionalUrl->route('catalog.index') }}">Каталог</a><a href="{{ $regionalUrl->route('favorites') }}">Избранное</a><a href="{{ $regionalUrl->route('orders.index') }}">Мои заказы</a></div>
                <div><strong>Помощь</strong><a href="{{ $regionalUrl->route('help') }}">Поддержка</a><a href="{{ $regionalUrl->route('help') }}">Доставка</a><a href="{{ $regionalUrl->route('gobuyer') }}">Продавцам</a></div>
                <div><strong>Ваш регион</strong><span>{{ $regionalContext->label() }}</span><a href="{{ $regionalUrl->route('catalog.index') }}">Смотреть товары</a></div>
            </div>
            @include('partials.seo.cities')
            <div class="joom-footer__bottom">© {{ now()->year }} BIMM · Маркетплейс Приднестровья</div>
        </div>
    </footer>

    <nav class="joom-bottom-nav" aria-label="Быстрая навигация">
        <a href="{{ $regionalUrl->route('home') }}"><i class="fa-solid fa-house"></i><span>Главная</span></a>
        <a href="{{ $regionalUrl->route('catalog.index') }}"><i class="fa-solid fa-grid-2"></i><span>Каталог</span></a>
        <a href="{{ $regionalUrl->route('favorites') }}"><i class="fa-regular fa-heart"></i><span>Избранное</span></a>
        <a href="{{ $regionalUrl->route('orders.index') }}"><i class="fa-solid fa-bag-shopping"></i><span>Заказы</span></a>
    </nav>

    <script src="@assetv('js/theme-joom.js')"></script>
    @stack('scripts')
</body>
</html>
