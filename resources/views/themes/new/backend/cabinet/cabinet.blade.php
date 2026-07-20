@extends('backend.cabinet.layout_cabinet')

@section('title', 'Обзор магазина')

@section('content_cabinet')
    @php
        $actionUrl = match ($sellerCabinetState->primaryAction) {
            'create_store' => route('account.store.create'),
            'choose_package', 'top_up_balance' => route('account.packages.index'),
            'choose_visible_products', 'remove_extra_vip', 'open_products' => route('account.store.products.index'),
            'edit_store' => $seller ? route('account.store.edit', $seller->id) : route('account.store.create'),
            'add_product' => route('account.store.products.create'),
            'open_store' => $seller ? route('account.store.index') : route('account.store.create'),
            default => route('account.index'),
        };
    @endphp

    <div class="cabinet-page-title">
        <div>
            <p class="eyebrow">BIMM SELLER STUDIO</p>
            <h1>Обзор магазина</h1>
            <p>Управляйте витриной, товарами и заказами из одного рабочего пространства.</p>
        </div>
        @if($seller)
            <a class="btn" href="{{ route('account.store.index') }}"><i class="fa-solid fa-store"></i>&nbsp; Открыть витрину</a>
        @endif
    </div>

    @if(!$seller)
        <section class="cabinet-empty-state card">
            <div class="cabinet-empty-state__icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
            <div>
                <span class="status-badge">Первый шаг</span>
                <h2>Создайте магазин и начните продавать</h2>
                <p>Заполните профиль бренда, добавьте товары — мы позаботимся о красивой витрине для покупателей.</p>
                <a class="btn" href="{{ $actionUrl }}">Создать магазин&nbsp; <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </section>
    @else
        <section class="cabinet-status cabinet-status--{{ $sellerCabinetState->alertType }}">
            <div class="cabinet-status__icon"><i class="fa-solid fa-{{ $sellerCabinetState->alertType === 'success' ? 'check' : 'bolt' }}"></i></div>
            <div class="cabinet-status__copy"><span class="eyebrow">Статус магазина</span><h2>{{ $sellerCabinetState->label }}</h2><p>{{ $sellerCabinetState->description }}</p></div>
            @if($sellerCabinetState->primaryActionLabel)
                <a class="btn" href="{{ $actionUrl }}">{{ $sellerCabinetState->primaryActionLabel }}&nbsp; <i class="fa-solid fa-arrow-right"></i></a>
            @endif
        </section>

        <div class="cabinet-stat-grid">
            <article class="card cabinet-stat"><span class="cabinet-stat__label">Активные товары</span><strong>{{ $sellerCabinetState->activeProductsCount }}</strong><small>{{ $sellerCabinetState->activeProductsLimit ? 'лимит '.$sellerCabinetState->activeProductsLimit : 'без лимита тарифа' }}</small></article>
            <article class="card cabinet-stat"><span class="cabinet-stat__label">В каталоге</span><strong>{{ $sellerCabinetState->visibleProductsCount }}</strong><small>видны покупателям</small></article>
            <article class="card cabinet-stat"><span class="cabinet-stat__label">VIP-размещение</span><strong>{{ $sellerCabinetState->vipProductsCount }}</strong><small>{{ $sellerCabinetState->vipProductsLimit ? 'лимит '.$sellerCabinetState->vipProductsLimit : 'доступно по тарифу' }}</small></article>
            <article class="card cabinet-stat"><span class="cabinet-stat__label">Баланс</span><strong>{{ number_format((float) $sellerCabinetState->balance, 0, ',', ' ') }} ₽</strong><small>на рекламные и тарифные услуги</small></article>
        </div>

        <div class="cabinet-dashboard-grid">
            <section class="card cabinet-package-card">
                <div class="card-header"><div><span class="eyebrow">Тариф и доступ</span><h2>{{ $sellerCabinetState->packageName ?: 'Тариф не выбран' }}</h2></div><i class="fa-solid fa-gem cabinet-card-icon"></i></div>
                @if($sellerCabinetState->hasActivePackage)
                    <div class="cabinet-package-card__meta"><span>Статус</span><strong>{{ $sellerCabinetState->packageIsLifetime ? 'Бессрочный доступ' : 'Активен' }}</strong></div>
                    @if(!$sellerCabinetState->packageIsLifetime && $sellerCabinetState->packageEndsAt)
                        <div class="cabinet-progress"><span style="width:{{ max(8, min(100, (($sellerCabinetState->packageDaysLeft ?? 0) / 30) * 100)) }}%"></span></div>
                        <div class="cabinet-package-card__meta"><span>Осталось дней</span><strong>{{ max(0, $sellerCabinetState->packageDaysLeft ?? 0) }}</strong></div>
                    @endif
                    <a class="btn btn-light" href="{{ route('account.packages.index') }}">Управлять тарифом</a>
                @else
                    <p>Выберите тариф, чтобы открыть товары для покупателей и активировать дополнительные возможности магазина.</p>
                    <a class="btn" href="{{ route('account.packages.index') }}">Выбрать тариф&nbsp; <i class="fa-solid fa-arrow-right"></i></a>
                @endif
            </section>

            <section class="card">
                <div class="card-header"><div><span class="eyebrow">Быстрые действия</span><h2>Следующий шаг</h2></div><i class="fa-solid fa-arrow-up-right-dots cabinet-card-icon"></i></div>
                <div class="cabinet-quick-actions">
                    <a href="{{ route('account.store.products.create') }}"><i class="fa-solid fa-plus"></i><span>Добавить товар</span><small>Новая позиция в каталоге</small></a>
                    <a href="{{ route('account.store.edit', $seller->id) }}"><i class="fa-solid fa-sliders"></i><span>Настроить магазин</span><small>Профиль, доставка и контакты</small></a>
<a href="{{ route('account.store.orders.index') }}"><i class="fa-solid fa-bag-shopping"></i><span>Проверить заказы</span><small>Обработайте новые заявки</small></a>
                </div>
            </section>
        </div>

        @if($sellerCabinetState->blockers !== [])
            <section class="card cabinet-attention-card">
                <div class="card-header"><div><span class="eyebrow">Требует внимания</span><h2>Подготовьте магазин к продажам</h2></div></div>
                <div class="cabinet-attention-list">
                    @foreach($sellerCabinetState->blockers as $blocker)
                        <div><span class="cabinet-attention-list__dot"></span><p>{{ $blocker['message'] }}</p>@if($blocker['action'])<a href="{{ match($blocker['action']) { 'choose_package', 'top_up_balance' => route('account.packages.index'), 'edit_store' => route('account.store.edit', $seller->id), default => route('account.store.products.index') } }}">Исправить&nbsp; <i class="fa-solid fa-arrow-right"></i></a>@endif</div>
                    @endforeach
                </div>
            </section>
        @endif
    @endif
@endsection
