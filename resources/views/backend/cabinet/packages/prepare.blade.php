{{-- Подготовка перехода на тариф --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
@php
    $activeLimit = $package->active_products_limit;
    $vipLimit = $package->vip_products_limit;
    $vipProducts = $activeProducts->where('is_vip', true)->values();
    $defaultActiveIds = old(
        'active_product_ids',
        $activeLimit === null
            ? $activeProducts->pluck('id')->all()
            : $activeProducts->take($activeLimit)->pluck('id')->all()
    );
    $defaultVipIds = old(
        'vip_product_ids',
        $vipLimit === null
            ? $vipProducts->whereIn('id', $defaultActiveIds)->pluck('id')->all()
            : $vipProducts->whereIn('id', $defaultActiveIds)->take($vipLimit)->pluck('id')->all()
    );
    $defaultActiveIds = array_map('intval', (array) $defaultActiveIds);
    $defaultVipIds = array_map('intval', (array) $defaultVipIds);
@endphp

<div class="contents package-prepare-page">
    <div class="container-fluid">

        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-main">
                    <h4 class="breadcrumb-title">Подготовить переход на тариф «{{ $package->name }}»</h4>
                    <div class="breadcrumb-action justify-content-center flex-wrap gap-2">
                        <a href="{{ route('account.packages.index') }}" class="btn btn-sm btn-secondary">
                            <i class="uil uil-arrow-left"></i> Назад к тарифам
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Переход не выполнен.</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="package-prepare-hero">
            <div>
                <span class="package-prepare-hero__eyebrow">Подготовка перехода</span>
                <h5>Выберите, какие товары останутся активными после подключения тарифа «{{ $package->name }}».</h5>
                <p>Лишние товары не удалятся. Они будут сняты с публикации, и вы сможете включить их позже на подходящем тарифе.</p>
            </div>
            <div class="package-prepare-hero__limits">
                <div>
                    <span>Активные товары</span>
                    <strong>{{ $compatibility->activeProductsCount }}{{ $activeLimit !== null ? ' / '.$activeLimit : ' / безлимит' }}</strong>
                </div>
                <div>
                    <span>VIP</span>
                    <strong>{{ $compatibility->vipProductsCount }}{{ $vipLimit !== null ? ' / '.$vipLimit : ' / безлимит' }}</strong>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <form action="{{ route('account.packages.prepare.apply', $package) }}" method="POST">
                    @csrf

                    @if(!$compatibility->activeProductsFit)
                        <div class="card mb-25 package-prepare-card">
                            <div class="card-header">
                                <h6 class="fw-500 mb-0">Выберите товары, которые останутся активными</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    Сейчас активно {{ $compatibility->activeProductsCount }} товаров.
                                    Тариф «{{ $package->name }}» разрешает
                                    {{ $activeLimit }}.
                                    Нужно снять с публикации {{ $compatibility->requiredActiveDeactivations }}.
                                </div>

                                <div class="row">
                                    @foreach($activeProducts as $product)
                                        <div class="col-md-6 mb-3">
                                            <label class="d-block border rounded p-3 h-100 package-prepare-product">
                                                <div class="d-flex gap-3">
                                                    <input type="checkbox"
                                                           class="form-check-input mt-1"
                                                           name="active_product_ids[]"
                                                           value="{{ $product->id }}"
                                                           @checked(in_array((int) $product->id, $defaultActiveIds, true))>
                                                    <div class="flex-grow-1">
                                                        <strong class="d-block">{{ $product->name }}</strong>
                                                        <small class="text-muted d-block">
                                                            {{ $product->category?->name ?? 'Без категории' }}
                                                        </small>
                                                        <small class="text-muted d-block">
                                                            {{ number_format($product->price, 0, '.', ' ') }} ₽
                                                        </small>
                                                        @if($product->is_vip)
                                                            <span class="badge badge-round badge-danger mt-2">VIP</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        @foreach($activeProducts as $product)
                            <input type="hidden" name="active_product_ids[]" value="{{ $product->id }}">
                        @endforeach
                    @endif

                    @if($vipProducts->isNotEmpty() && (!$compatibility->vipProductsFit || !$compatibility->activeProductsFit))
                        <div class="card mb-25 package-prepare-card">
                            <div class="card-header">
                                <h6 class="fw-500 mb-0">Выберите VIP-товары</h6>
                            </div>
                            <div class="card-body">
                                @if(!$compatibility->vipProductsFit)
                                    <div class="alert alert-warning">
                                        Сейчас VIP-товаров {{ $compatibility->vipProductsCount }}.
                                        Тариф «{{ $package->name }}» разрешает
                                        {{ $vipLimit }}.
                                        Нужно снять VIP с {{ $compatibility->requiredVipDeactivations }}.
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        VIP сохранится только у товаров, которые останутся активными после перехода.
                                    </div>
                                @endif

                                @if($vipLimit === 0)
                                    <p class="text-muted">На этом тарифе VIP-товары недоступны. VIP-метки будут сняты со всех активных товаров.</p>
                                @else
                                    <div class="row">
                                        @foreach($vipProducts as $product)
                                            <div class="col-md-6 mb-3">
                                                <label class="d-block border rounded p-3 h-100 package-prepare-product">
                                                    <div class="d-flex gap-3">
                                                        <input type="checkbox"
                                                               class="form-check-input mt-1"
                                                               name="vip_product_ids[]"
                                                               value="{{ $product->id }}"
                                                               @checked(in_array((int) $product->id, $defaultVipIds, true))>
                                                        <div class="flex-grow-1">
                                                            <strong class="d-block">{{ $product->name }}</strong>
                                                            <small class="text-muted d-block">Оставить VIP после перехода</small>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        @foreach($vipProducts as $product)
                            <input type="hidden" name="vip_product_ids[]" value="{{ $product->id }}">
                        @endforeach
                    @endif

                    <div class="card mb-25 package-prepare-confirm">
                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div>
                                <h6 class="mb-1">Подтверждение</h6>
                                <p class="mb-0 text-muted">
                                    После подтверждения лишние товары будут сняты с публикации, лишние VIP-метки удалены,
                                    затем тариф будет подключён.
                                </p>
                            </div>
                            <button type="submit" class="btn btn-primary btn-squared">
                                Подтвердить и подключить тариф
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="card mb-25 package-prepare-summary">
                    <div class="card-header">
                        <h6 class="fw-500 mb-0">Что изменится</h6>
                    </div>
                    <div class="card-body">
                        <ul class="ps-3">
                            <li>
                                Активные товары:
                                {{ $compatibility->activeProductsCount }}
                                @if($activeLimit !== null)
                                    → {{ $activeLimit }}
                                @endif
                            </li>
                            <li>
                                VIP:
                                {{ $compatibility->vipProductsCount }}
                                @if($vipLimit !== null)
                                    → {{ $vipLimit }}
                                @endif
                            </li>
                            <li>Цена тарифа: {{ number_format($package->price, 0, '.', ' ') }} ₽</li>
                        </ul>

                        @if($compatibility->warnings !== [])
                            <div class="alert alert-warning mt-3 mb-0">
                                @foreach($compatibility->warnings as $warning)
                                    <div>{{ $warning }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
