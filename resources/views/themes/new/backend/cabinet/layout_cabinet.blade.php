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
        $cabinetProfile = html_entity_decode('&#1055;&#1088;&#1086;&#1092;&#1080;&#1083;&#1100;');
        $cabinetSecurity = html_entity_decode('&#1041;&#1077;&#1079;&#1086;&#1087;&#1072;&#1089;&#1085;&#1086;&#1089;&#1090;&#1100;');
        $cabinetMarketplaceBack = html_entity_decode('&#1042;&#1077;&#1088;&#1085;&#1091;&#1090;&#1100;&#1089;&#1103; &#1074; &#1084;&#1072;&#1088;&#1082;&#1077;&#1090;&#1087;&#1083;&#1077;&#1081;&#1089;');
        $cabinetGuestLabel = html_entity_decode('&#1055;&#1088;&#1086;&#1076;&#1072;&#1074;&#1077;&#1094;');
    @endphp
    <title>{{ trim($__env->yieldContent('title', $cabinetDefaultTitle)) }}</title>
    <link rel="icon" href="{{ asset('img/favicon.png') }}?v={{ filemtime(public_path('img/favicon.png')) }}" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/bootstrap/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cabinet-common.css') }}?v={{ filemtime(public_path('css/cabinet-common.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/theme-cabinet-new.css') }}?v={{ filemtime(public_path('css/theme-cabinet-new.css')) }}">
    @stack('styles')
</head>
<body class="cabinet-new">
<div class="cabinet-shell">
    <header class="cabinet-topbar">
        <a class="cabinet-brand" href="{{ route('account.index') }}">
            <span class="cabinet-brand__mark" aria-hidden="true"></span>
            <span class="cabinet-brand__copy">
                <span class="cabinet-brand__title">BIMM</span>
                <span class="cabinet-brand__subtitle">Seller studio</span>
            </span>
        </a>

        <div class="cabinet-topbar__right">
            <a href="{{ route('home') }}" target="_blank" rel="noopener">
                <i class="fa-solid fa-arrow-up-right-from-square"></i><span class="cabinet-label">{{ $cabinetOpenSite }}</span>
            </a>
            @auth
                @php
                    $cabinetNotifications = auth()->user()->notifications()->latest()->limit(8)->get();
                    $unreadNotificationsCount = auth()->user()->unreadNotifications()->count();
                @endphp
                <div class="dropdown cabinet-bell">
                    <button type="button" class="cabinet-bell__toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Уведомления">
                        <i class="fa-regular fa-bell"></i>
                        @if($unreadNotificationsCount > 0)
                            <span class="cabinet-bell__badge">{{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end cabinet-bell__menu">
                        <div class="cabinet-bell__head">Уведомления</div>
                        <div class="cabinet-bell__list">
                            @forelse($cabinetNotifications as $notification)
                                <a class="cabinet-bell__item {{ $notification->read_at ? '' : 'cabinet-bell__item--unread' }}"
                                   href="{{ route('account.notifications.read', $notification->id) }}">
                                    <strong>{{ $notification->data['subject'] ?? $notification->data['product_name'] ?? 'Уведомление' }}</strong>
                                    <span>{{ $notification->data['title'] ?? $notification->data['status_label'] ?? 'Статус изменён' }}</span>
                                    @if(!empty($notification->data['status_label']) && !empty($notification->data['title']))
                                        <span>{{ $notification->data['status_label'] }}</span>
                                    @endif
                                    @if(!empty($notification->data['comment']))
                                        <span>{{ \Illuminate\Support\Str::limit($notification->data['comment'], 80) }}</span>
                                    @endif
                                    <small>{{ $notification->created_at->diffForHumans() }}</small>
                                </a>
                            @empty
                                <div class="cabinet-bell__empty">Уведомлений пока нет</div>
                            @endforelse
                        </div>
                        @if($unreadNotificationsCount > 0)
                            <form method="POST" action="{{ route('account.notifications.read-all') }}" class="cabinet-bell__footer">
                                @csrf
                                <button type="submit">Отметить все прочитанными</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endauth
            <span class="cabinet-user">
                <span class="cabinet-user__avatar"><i class="fa-regular fa-user"></i></span>
                <span class="cabinet-user__name">{{ $user?->name ?: $user?->email ?: $cabinetGuestLabel }}</span>
            </span>
            @auth
                <a class="cabinet-logout" href="{{ route('logout') }}" title="Выйти из кабинета">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i><span class="cabinet-label">Выйти</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            @endauth
        </div>
    </header>

    <div class="cabinet-layout">
        <aside class="cabinet-sidebar">
            <p class="cabinet-sidebar__label">{{ $cabinetWorkspace }}</p>
            <nav>
                <a class="{{ request()->routeIs('account.index') ? 'active' : '' }}" href="{{ route('account.index') }}">
                    <i class="fa-solid fa-table-cells-large"></i><span>{{ $cabinetOverview }}</span>
                </a>
                @if($seller)
                    <a class="{{ request()->routeIs('account.store.products*') ? 'active' : '' }}" href="{{ route('account.store.products.index') }}">
                        <i class="fa-solid fa-boxes-stacked"></i><span>{{ $cabinetProducts }}</span>
                    </a>
                    <a class="{{ request()->routeIs('account.vip.*') ? 'active' : '' }}" href="{{ route('account.vip.index') }}">
                        <i class="fa-solid fa-star"></i><span>VIP-размещение</span>
                    </a>
                    <a class="{{ request()->routeIs('account.store.orders*') ? 'active' : '' }}" href="{{ route('account.store.orders.index') }}">
                        <i class="fa-solid fa-bag-shopping"></i><span>{{ $cabinetOrders }}</span>
                    </a>
                    <a class="{{ request()->routeIs('account.stats') ? 'active' : '' }}" href="{{ route('account.stats') }}">
                        <i class="fa-solid fa-chart-line"></i><span>{{ $cabinetStats }}</span>
                    </a>
                @endif
                <a class="{{ request()->routeIs('account.balance.*', 'account.user.balance.*') ? 'active' : '' }}" href="{{ route('account.balance.index') }}">
                    <i class="fa-solid fa-wallet"></i><span>Баланс</span>
                </a>
                <a class="{{ request()->routeIs('account.user.index', 'account.user.edit', 'account.user.update') ? 'active' : '' }}" href="{{ route('account.user.index') }}">
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
                    <div class="alert cabinet-flash cabinet-flash--success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert cabinet-flash cabinet-flash--error">{{ session('error') }}</div>
                @endif
                @if(session('warning'))
                    <div class="alert cabinet-flash cabinet-flash--error">{{ session('warning') }}</div>
                @endif
                @if(session('info'))
                    <div class="alert cabinet-flash cabinet-flash--success">{{ session('info') }}</div>
                @endif

                @yield('content_cabinet')
            </div>
        </main>
    </div>
</div>

<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery/jquery-3.5.1.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/bootstrap/popper.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/bootstrap/bootstrap.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    $('a.cabinet-logout').on('click', function (e) {
        e.preventDefault();
        Swal.fire({
            title: 'Вы уверены?',
            text: 'Вы действительно хотите выйти из кабинета?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff5a1f',
            confirmButtonText: 'Выйти',
            cancelButtonText: 'Отмена'
        }).then(function (result) {
            if (result.isConfirmed) {
                $('#logout-form').submit();
            }
        });
    });
});
</script>
<script>
(function () {
    function updateCharacterCounter(field, counter, maxLength) {
        const length = field.value.length;
        counter.textContent = length + '/' + maxLength;
        counter.classList.toggle('seller-character-counter--warning', length >= Math.floor(maxLength * 0.9) && length < maxLength);
        counter.classList.toggle('seller-character-counter--limit', length >= maxLength);
    }

    function initCharacterCounter(field) {
        const type = (field.getAttribute('type') || '').toLowerCase();
        const maxLength = Number(field.getAttribute('maxlength'));

        if (!Number.isFinite(maxLength) || maxLength <= 0 || field.dataset.characterCounterInitialized === '1') {
            return;
        }

        if (['hidden', 'file', 'checkbox', 'radio'].includes(type)) {
            return;
        }

        if (field.hasAttribute('data-digits-only')) {
            return;
        }

        if (field.hasAttribute('data-no-character-counter')) {
            return;
        }

        if (field.id && document.getElementById(field.id + '-counter')) {
            return;
        }

        const counter = document.createElement('small');
        counter.className = 'seller-character-counter';
        counter.setAttribute('aria-live', 'polite');

        const anchor = field.closest('.input-group') || field;
        anchor.insertAdjacentElement('afterend', counter);

        field.dataset.characterCounterInitialized = '1';
        field.addEventListener('input', function () {
            updateCharacterCounter(field, counter, maxLength);
        });
        updateCharacterCounter(field, counter, maxLength);
    }

    function initDigitsOnly(field) {
        if (field.dataset.digitsOnlyInitialized === '1') {
            return;
        }

        const normalize = function () {
            const digits = field.value.replace(/\D/g, '');

            if (field.value !== digits) {
                field.value = digits;
                field.dispatchEvent(new Event('input', { bubbles: true }));
            }
        };

        field.dataset.digitsOnlyInitialized = '1';
        field.addEventListener('input', normalize);
        normalize();
    }

    function bootSellerFormHelpers() {
        document.querySelectorAll('input[maxlength], textarea[maxlength]').forEach(initCharacterCounter);
        document.querySelectorAll('[data-digits-only]').forEach(initDigitsOnly);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootSellerFormHelpers);
    } else {
        bootSellerFormHelpers();
    }
})();
</script>
@stack('scripts')
</body>
</html>
