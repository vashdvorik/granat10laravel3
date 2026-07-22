<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $cabinetDefaultTitle = html_entity_decode('&#1050;&#1072;&#1073;&#1080;&#1085;&#1077;&#1090; &#1087;&#1088;&#1086;&#1076;&#1072;&#1074;&#1094;&#1072;');
        $cabinetOpenSite = html_entity_decode('&#1054;&#1090;&#1082;&#1088;&#1099;&#1090;&#1100; &#1089;&#1072;&#1081;&#1090;');
        $cabinetWorkspace = html_entity_decode('&#1056;&#1072;&#1073;&#1086;&#1095;&#1077;&#1077; &#1087;&#1088;&#1086;&#1089;&#1090;&#1088;&#1072;&#1085;&#1089;&#1090;&#1074;&#1086;');
        $cabinetOverview = html_entity_decode('&#1054;&#1073;&#1079;&#1086;&#1088;');
        $cabinetStore = html_entity_decode('&#1052;&#1072;&#1075;&#1072;&#1079;&#1080;&#1085;');
        $cabinetProducts = html_entity_decode('&#1058;&#1086;&#1074;&#1072;&#1088;&#1099;');
        $cabinetOrders = html_entity_decode('&#1047;&#1072;&#1082;&#1072;&#1079;&#1099;');
        $cabinetStats = html_entity_decode('&#1040;&#1085;&#1072;&#1083;&#1080;&#1090;&#1080;&#1082;&#1072;');
        $cabinetPackages = html_entity_decode('&#1058;&#1072;&#1088;&#1080;&#1092;&#1099;');
        $cabinetProfile = html_entity_decode('&#1055;&#1088;&#1086;&#1092;&#1080;&#1083;&#1100;');
        $cabinetSecurity = html_entity_decode('&#1041;&#1077;&#1079;&#1086;&#1087;&#1072;&#1089;&#1085;&#1086;&#1089;&#1090;&#1100;');
        $cabinetMarketplaceBack = html_entity_decode('&#1042;&#1077;&#1088;&#1085;&#1091;&#1090;&#1100;&#1089;&#1103; &#1074; &#1084;&#1072;&#1088;&#1082;&#1077;&#1090;&#1087;&#1083;&#1077;&#1081;&#1089;');
        $cabinetGuestLabel = html_entity_decode('&#1055;&#1088;&#1086;&#1076;&#1072;&#1074;&#1077;&#1094;');
    @endphp
    <title>{{ trim($__env->yieldContent('title', $cabinetDefaultTitle)) }}</title>
    <link rel="icon" href="{{ asset('img/favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/theme-cabinet-new.css') }}">
    @stack('styles')
</head>
<body class="cabinet-new">
<div class="cabinet-shell">
    <header class="cabinet-topbar">
        <a class="cabinet-brand" href="{{ route('account.index') }}">
            <img class="cabinet-brand__logo" src="{{ asset('img/logo.png') }}" alt="BIMM">
        </a>

        <div class="cabinet-topbar__right">
            <a href="{{ route('home') }}" target="_blank" rel="noopener">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> {{ $cabinetOpenSite }}
            </a>
            <span class="cabinet-user">
                <span class="cabinet-user__avatar"><i class="fa-regular fa-user"></i></span>
                {{ $user?->name ?: $user?->email ?: $cabinetGuestLabel }}
            </span>
        </div>
    </header>

    <div class="cabinet-layout">
        <aside class="cabinet-sidebar">
            <p class="cabinet-sidebar__label">{{ $cabinetWorkspace }}</p>
            <nav>
                <a class="{{ request()->routeIs('account.index') ? 'active' : '' }}" href="{{ route('account.index') }}">
                    <i class="fa-solid fa-grid-2"></i><span>{{ $cabinetOverview }}</span>
                </a>
                @if($seller)
                    <a class="{{ request()->routeIs('account.store.index') ? 'active' : '' }}" href="{{ route('account.store.index') }}">
                        <i class="fa-solid fa-store"></i><span>{{ $cabinetStore }}</span>
                    </a>
                    <a class="{{ request()->routeIs('account.store.products*') ? 'active' : '' }}" href="{{ route('account.store.products.index') }}">
                        <i class="fa-solid fa-boxes-stacked"></i><span>{{ $cabinetProducts }}</span>
                    </a>
                    <a class="{{ request()->routeIs('account.store.orders*') ? 'active' : '' }}" href="{{ route('account.store.orders.index') }}">
                        <i class="fa-solid fa-bag-shopping"></i><span>{{ $cabinetOrders }}</span>
                    </a>
                    <a class="{{ request()->routeIs('account.stats') ? 'active' : '' }}" href="{{ route('account.stats') }}">
                        <i class="fa-solid fa-chart-line"></i><span>{{ $cabinetStats }}</span>
                    </a>
                    <a class="{{ request()->routeIs('account.packages*') ? 'active' : '' }}" href="{{ route('account.packages.index') }}">
                        <i class="fa-solid fa-gem"></i><span>{{ $cabinetPackages }}</span>
                    </a>
                @endif
                <a class="{{ request()->routeIs('account.user*') ? 'active' : '' }}" href="{{ route('account.user.index') }}">
                    <i class="fa-regular fa-circle-user"></i><span>{{ $cabinetProfile }}</span>
                </a>
                <a class="{{ request()->routeIs('account.user.change_password') ? 'active' : '' }}" href="{{ route('account.user.change_password') }}">
                    <i class="fa-solid fa-lock"></i><span>{{ $cabinetSecurity }}</span>
                </a>
            </nav>
            <a class="cabinet-sidebar__back" href="{{ route('home') }}">
                <i class="fa-solid fa-arrow-left"></i> {{ $cabinetMarketplaceBack }}
            </a>
        </aside>

        <main class="cabinet-content">
            <div class="cabinet-content__inner">
                @if(session('success'))
                    <div class="alert" style="margin-bottom:18px">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert" style="margin-bottom:18px;background:#fff0ee;border-color:#f0cdca;color:#8b3d36">{{ session('error') }}</div>
                @endif

                @yield('content_cabinet')
            </div>
        </main>
    </div>
</div>

<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery-3.6.0.min.js') }}"></script>
@stack('scripts')
</body>
</html>
