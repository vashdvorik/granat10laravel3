<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trim($__env->yieldContent('title', 'Кабинет продавца')) }}</title>
    <link rel="icon" href="{{ asset('img/favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/theme-cabinet-new.css') }}">
    @stack('styles')
</head>
<body class="cabinet-new">
<div class="cabinet-shell">
    <header class="cabinet-topbar">
        <a class="cabinet-brand" href="{{ route('account.index') }}"><span class="cabinet-brand__mark">B</span><span>BIMM / SELLER</span></a>
        <div class="cabinet-topbar__right"><a href="{{ route('home') }}" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Открыть сайт</a><span class="cabinet-user"><span class="cabinet-user__avatar"><i class="fa-regular fa-user"></i></span>{{ $user?->name ?: $user?->email ?: 'Продавец' }}</span></div>
    </header>
    <div class="cabinet-layout">
        <aside class="cabinet-sidebar">
            <p class="cabinet-sidebar__label">Рабочее пространство</p>
            <nav>
                <a class="{{ request()->routeIs('account.index') ? 'active' : '' }}" href="{{ route('account.index') }}"><i class="fa-solid fa-grid-2"></i><span>Обзор</span></a>
                @if($seller)
                    <a class="{{ request()->routeIs('account.store.index') ? 'active' : '' }}" href="{{ route('account.store.index') }}"><i class="fa-solid fa-store"></i><span>Магазин</span></a>
                    <a class="{{ request()->routeIs('account.store.products*') ? 'active' : '' }}" href="{{ route('account.store.products.index') }}"><i class="fa-solid fa-boxes-stacked"></i><span>Товары</span></a>
<a class="{{ request()->routeIs('account.store.orders*') ? 'active' : '' }}" href="{{ route('account.store.orders.index') }}"><i class="fa-solid fa-bag-shopping"></i><span>Заказы</span></a>
                    <a class="{{ request()->routeIs('account.stats') ? 'active' : '' }}" href="{{ route('account.stats') }}"><i class="fa-solid fa-chart-line"></i><span>Аналитика</span></a>
                    <a class="{{ request()->routeIs('account.packages*') ? 'active' : '' }}" href="{{ route('account.packages.index') }}"><i class="fa-solid fa-gem"></i><span>Тарифы</span></a>
                @endif
                <a class="{{ request()->routeIs('account.user*') ? 'active' : '' }}" href="{{ route('account.user.index') }}"><i class="fa-regular fa-circle-user"></i><span>Профиль</span></a>
                <a class="{{ request()->routeIs('account.user.change_password') ? 'active' : '' }}" href="{{ route('account.user.change_password') }}"><i class="fa-solid fa-lock"></i><span>Безопасность</span></a>
            </nav>
            <a class="cabinet-sidebar__back" href="{{ route('home') }}"><i class="fa-solid fa-arrow-left"></i> Вернуться на сайт</a>
        </aside>
        <main class="cabinet-content"><div class="cabinet-content__inner">@if(session('success'))<div class="alert" style="margin-bottom:18px">{{ session('success') }}</div>@endif @if(session('error'))<div class="alert" style="margin-bottom:18px;background:#fff0ee;border-color:#f0cdca;color:#8b3d36">{{ session('error') }}</div>@endif @yield('content_cabinet')</div></main>
    </div>
</div>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery-3.6.0.min.js') }}"></script>
@stack('scripts')
</body>
</html>
