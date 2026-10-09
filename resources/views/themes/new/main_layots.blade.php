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
    <link rel="stylesheet" href="{{ asset('css/theme-new.css') }}">
    @stack('head')
    @stack('styles')
</head>
<body class="theme-new">
    <header class="bimm-header">
        <div class="bimm-wrap bimm-header__inner">
            <a class="bimm-brand" href="{{ $regionalUrl->route('home') }}" aria-label="BIMM — маркетплейс Приднестровья">
                <span class="bimm-brand__mark" aria-hidden="true"></span><span class="bimm-brand__label"><span>BIMM</span><small>Маркетплейс Приднестровья</small></span>
            </a>

            <nav class="bimm-nav" aria-label="Основная навигация">
                <a href="{{ $regionalUrl->route('catalog.index') }}">Каталог</a>
                <a href="{{ $regionalUrl->route('catalog.sale') }}">Скидки</a>
                <a href="{{ $regionalUrl->route('shops') }}">Магазины</a>
                <a href="{{ $regionalUrl->route('help') }}">Помощь</a>
                <a class="bimm-nav__seller" href="{{ $regionalUrl->route('gobuyer') }}">Продавцам</a>
            </nav>

            <div class="bimm-actions">
                <form class="bimm-search" action="{{ $regionalUrl->route('search') }}" method="GET" role="search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Найти товар или магазин" aria-label="Найти товар или магазин">
                    <button type="submit" aria-label="Искать"><i class="fa-solid fa-arrow-right"></i></button>
                </form>
                <a class="bimm-icon-link" href="{{ $regionalUrl->route('favorites') }}" aria-label="Избранное"><i class="fa-regular fa-heart"></i></a>
                <a class="bimm-icon-link" href="{{ $regionalUrl->route('orders.index') }}" aria-label="Заказы"><i class="fa-solid fa-bag-shopping"></i></a>
                <button class="bimm-menu-toggle" type="button" data-bimm-menu-toggle aria-expanded="false" aria-label="Открыть меню"><i class="fa-solid fa-bars"></i></button>
            </div>
        </div>
        <nav class="bimm-wrap bimm-mobile-nav" data-bimm-mobile-nav aria-label="Мобильная навигация">
            <form class="bimm-search bimm-search--hero" action="{{ $regionalUrl->route('search') }}" method="GET">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" name="q" placeholder="Найти товар или магазин" aria-label="Найти товар или магазин">
                <button type="submit" aria-label="Искать"><i class="fa-solid fa-arrow-right"></i></button>
            </form>
            <a href="{{ $regionalUrl->route('catalog.index') }}">Каталог</a>
            <a href="{{ $regionalUrl->route('catalog.sale') }}">Скидки</a>
            <a href="{{ $regionalUrl->route('shops') }}">Магазины</a>
            @auth
                <a href="{{ route('account.index') }}">Кабинет продавца</a>
            @endauth
        </nav>
    </header>

    <main class="bimm-main">
        <div class="bimm-wrap">
            @if(session('success'))<div class="bimm-flash">{{ session('success') }}</div>@endif
            @if(session('status'))<div class="bimm-flash">{{ session('status') }}</div>@endif
            @yield('content')
        </div>
    </main>

    <footer class="bimm-footer">
        <div class="bimm-wrap">
            <div class="bimm-footer__grid">
                <div>
                    <a class="bimm-brand" href="{{ $regionalUrl->route('home') }}" aria-label="BIMM — маркетплейс Приднестровья"><span class="bimm-brand__mark" aria-hidden="true"></span><span class="bimm-brand__label"><span>BIMM</span><small>Маркетплейс Приднестровья</small></span></a>
                    <p style="margin-top:15px;max-width:280px">Товары и магазины Приднестровья в одном месте. Сравнивайте предложения и связывайтесь с продавцом напрямую.</p>
                </div>
                <div><h3>Покупателям</h3><a href="{{ $regionalUrl->route('catalog.index') }}">Каталог</a><a href="{{ $regionalUrl->route('favorites') }}">Избранное</a><a href="{{ $regionalUrl->route('orders.index') }}">Мои заказы</a></div>
                <div><h3>Сообщество</h3><a href="{{ $regionalUrl->route('shops') }}">Магазины</a><a href="{{ $regionalUrl->route('gobuyer') }}">Продавцам</a><a href="{{ $regionalUrl->route('help') }}">Помощь</a></div>
                <div><h3>Регион</h3><p>{{ $regionalContext->label() }}</p><a href="{{ $regionalUrl->route('catalog.index') }}">Смотреть предложения</a></div>
            </div>
            <div class="bimm-footer__assurance" aria-label="Преимущества BIMM">
                <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Проверенные продавцы</span>
                <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Всё рядом</span>
                <span><i class="fa-solid fa-bolt" aria-hidden="true"></i> Быстрый контакт</span>
                <span><i class="fa-solid fa-tag" aria-hidden="true"></i> Новые скидки</span>
            </div>
            @include('partials.seo.cities')
            <div class="bimm-footer__bottom">© {{ now()->year }} BIMM · Локальный маркетплейс Приднестровья</div>
        </div>
    </footer>

    <nav class="bimm-bottom-nav" aria-label="Быстрая навигация">
        <a href="{{ $regionalUrl->route('home') }}"><i class="fa-solid fa-house"></i><span>Главная</span></a>
        <a href="{{ $regionalUrl->route('catalog.index') }}"><i class="fa-solid fa-grid-2"></i><span>Каталог</span></a>
        <a href="{{ $regionalUrl->route('favorites') }}"><i class="fa-regular fa-heart"></i><span>Избранное</span></a>
        <a href="{{ $regionalUrl->route('orders.index') }}"><i class="fa-solid fa-bag-shopping"></i><span>Заказы</span></a>
    </nav>

    <script src="{{ asset('js/theme-new.js') }}"></script>
    @stack('scripts')
</body>
</html>
