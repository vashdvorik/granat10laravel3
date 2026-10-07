{{-- Главная страница кабинета продавца: состояние магазина, информация, контакты, баланс --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')

<div class="contents store-overview-page">
    <div class="container-fluid">
        @include('backend.cabinet.partials.flash')
        @if(!$seller)
            @include('backend.cabinet.store.nostore')
        @else
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb-main">
                        <h4 class="breadcrumb-title">Общая информация о магазине</h4>
                    </div>
                </div>
            </div>

            @isset($sellerCabinetState)
                <div class="store-overview-hero store-overview-hero--compact">
                    <div class="store-overview-hero__main">
                        <span class="store-overview-hero__eyebrow">{{ $sellerCabinetState->label }}</span>
                        <h5>{{ $seller->name }}</h5>
                        @if($sellerCabinetState->blockers !== [])
                            @foreach(array_slice($sellerCabinetState->blockers, 0, 2) as $blocker)
                                <p class="mb-1">{{ $blocker['message'] }}</p>
                            @endforeach
                        @else
                            <p>{{ $sellerCabinetState->description }}</p>
                        @endif
                        @php
                            $stateActionUrl = match ($sellerCabinetState->primaryAction) {
                                'create_store' => route('account.store.create'),
                                'open_products' => route('account.store.products.index'),
                                'add_product' => route('account.store.products.create'),
                                'edit_store' => route('account.store.edit', $seller),
                                'open_store' => route('seller', $seller->alias),
                                default => null,
                            };
                        @endphp
                        @if($stateActionUrl && $sellerCabinetState->primaryActionLabel)
                            <a href="{{ $stateActionUrl }}" class="btn btn-sm btn-primary">
                                {{ $sellerCabinetState->primaryActionLabel }}
                            </a>
                        @endif
                    </div>
                    <div class="store-overview-hero__stats store-overview-hero__stats--two">
                        <div>
                            <span>В каталоге</span>
                            <strong>{{ $sellerCabinetState->visibleProductsCount }}</strong>
                        </div>
                        <div>
                            <span>VIP</span>
                            <strong>{{ $sellerCabinetState->vipProductsCount }}</strong>
                        </div>
                    </div>
                </div>
            @endisset

            <div class="row">
                <div class="col-lg-8">
                    @include('backend.cabinet.partials.store-profile')
                </div>

                <div class="col-lg-4">
                    <div class="card mb-25 store-tariff-card">
                        <div class="card-header color-dark fw-500">
                            <h6 class="fw-500">Баланс</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <div class="fs-20 fw-bold text-success">{{ number_format((float) ($seller->user?->balance ?? 0), 2, ',', ' ') }} руб.</div>
                                <small class="text-muted d-block mt-2">Баланс используется для оплаты VIP-размещения товаров. VIP подключается на странице товара.</small>
                                <a href="{{ route('account.user.balance.topup') }}" class="btn btn-success btn-sm btn-squared mt-3">Пополнить баланс</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
