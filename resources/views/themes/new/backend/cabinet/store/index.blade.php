{{-- Магазин — общая информация --}}

@extends('backend.cabinet.layout_cabinet')
@inject('productLimitService', 'App\Services\ProductLimitService')

@section('content_cabinet')

<div class="contents store-overview-page">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(!$seller)
            @include('backend.cabinet.store.nostore')
        @else
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb-main">
                        <h4 class="breadcrumb-title">Общая информация о магазине</h4>
                        <div class="breadcrumb-action justify-content-center flex-wrap">
  
                        </div>
                    </div>
                </div>
            </div>

            @isset($sellerCabinetState)
                <div class="store-overview-hero">
                    <div class="store-overview-hero__main">
                        <span class="store-overview-hero__eyebrow">Состояние магазина</span>
                        <h5>{{ $seller->name }}</h5>
                        <p>{{ $sellerCabinetState->description }}</p>
                        @php
                            $stateActionUrl = match ($sellerCabinetState->primaryAction) {
                                'choose_package' => route('account.packages.index'),
                                'add_products' => route('account.store.products.create'),
                                'fix_profile' => route('account.store.edit', $seller),
                                'choose_visible_products' => route('account.packages.index'),
                                default => null,
                            };
                        @endphp
                        @if($stateActionUrl && $sellerCabinetState->primaryActionLabel)
                            <a href="{{ $stateActionUrl }}" class="btn btn-sm btn-primary">
                                {{ $sellerCabinetState->primaryActionLabel }}
                            </a>
                        @endif
                    </div>
                    <div class="store-overview-hero__status store-overview-hero__status--{{ $sellerCabinetState->alertType }}">
                        <span>{{ $sellerCabinetState->label }}</span>
                        <strong>{{ $sellerCabinetState->packageName ?? 'Нет тарифа' }}</strong>
                    </div>
                    <div class="store-overview-hero__stats">
                        <div>
                            <span>Видны</span>
                            <strong>{{ $sellerCabinetState->visibleProductsCount }}</strong>
                        </div>
                        <div>
                            <span>Активные</span>
                            <strong>
                                {{ $sellerCabinetState->activeProductsCount }}
                                @if($sellerCabinetState->activeProductsLimit !== null)
                                    / {{ $sellerCabinetState->activeProductsLimit }}
                                @endif
                            </strong>
                        </div>
                        <div>
                            <span>VIP</span>
                            <strong>
                                {{ $sellerCabinetState->vipProductsCount }}
                                @if($sellerCabinetState->vipProductsLimit !== null)
                                    / {{ $sellerCabinetState->vipProductsLimit }}
                                @endif
                            </strong>
                        </div>
                    </div>
                </div>
            @endisset

            <div class="row">
                {{-- Основная информация --}}
                <div class="col-lg-8">

                    <div class="card mb-25 store-overview-card">
                        <div class="card-header">
                            <h6 class="fw-500 mb-0">Основная информация</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded bg-light-subtle store-info-tile">
                                        <div class="d-flex align-items-start">
                                            <div class="me-3">
                                                <i class="uil uil-store text-primary fs-24"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted d-block mb-1">Название магазина</small>
                                                <strong class="d-block">{{ $seller->name }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="p-3 border rounded bg-light-subtle store-info-tile">
                                        <div class="d-flex align-items-start">
                                            <div class="me-3">
                                                <i class="uil uil-phone text-success fs-24"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted d-block mb-1">Телефон магазина</small>
                                                <strong class="d-block">{{ $seller->phone ?? 'Не указан' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="p-3 border rounded bg-light-subtle store-info-tile">
                                        <div class="d-flex align-items-start">
                                            <div class="me-3">
                                                <i class="uil uil-map-marker text-danger fs-24"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted d-block mb-1">Город</small>
                                                <strong class="d-block">{{ $seller->town ?? 'Не указан' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="p-3 border rounded bg-light-subtle store-info-tile">
                                        <div class="d-flex align-items-start">
                                            <div class="me-3">
                                                <i class="uil uil-location-point text-info fs-24"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted d-block mb-1">Район</small>
                                                <strong class="d-block">{{ $seller->sellerplace ?? 'Не указан' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="p-3 border rounded bg-light-subtle store-info-tile">
                                        <div class="d-flex align-items-start">
                                            <div class="me-3">
                                                <i class="uil uil-map-pin-alt text-warning fs-24"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted d-block mb-1">Адрес</small>
                                                <strong class="d-block">{{ $seller->address ?? 'Не указан' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if($seller->description)
                                <div class="col-12">
                                    <div class="p-3 border rounded bg-light-subtle store-info-tile">
                                        <div class="d-flex align-items-start">
                                            <div class="me-3">
                                                <i class="uil uil-file-alt text-secondary fs-24"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted d-block mb-1">Описание</small>
                                                <p class="mb-0">{{ $seller->description }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top">
                            <div class="d-flex justify-content-end">
                                <a href="{{ route('account.store.edit', $seller->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="uil uil-edit"></i> Редактировать
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Социальные сети и контакты --}}
                    <div class="card mb-25 store-overview-card store-contact-card">
                        <div class="card-header">
                            <h6 class="fw-500 mb-0">Контакты и соцсети</h6>
                        </div>
                        <div class="card-body">
                            @include('backend.cabinet.partials.store-feature-grid', [
                                'seller' => $seller,
                                'sellerCabinetState' => $sellerCabinetState ?? null,
                            ])
                        </div>
                        <div class="card-footer bg-transparent border-top">
                            <div class="d-flex justify-content-end gap-2">
                                    @if(isset($sellerCabinetState) && (! $sellerCabinetState->canShowWebsite || ! $sellerCabinetState->canShowSocialLinks))
                                          <a href="{{ route('account.packages.index') }}" class="btn btn-sm btn-warning">
                                             <i class="uil uil-arrow-circle-up"></i> Обновить тариф
                                          </a>
                                    @endif                                
                                    <a href="{{ route('account.store.edit', $seller->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="uil uil-edit"></i> Редактировать
                                    </a>

                            </div>
                        </div>
                    </div>
                </div>

                {{-- Боковая панель --}}
                <div class="col-lg-4">


                    {{-- Активный тариф --}}
                    @php
                        $activePackage = $seller->activePackage();
                    @endphp
                    <div class="card mb-25 store-tariff-card">
                        <div class="card-header color-dark fw-500">
                            <h6 class="fw-500">Текущий тариф</h6>
                        </div>
                        <div class="card-body">
                            @if($activePackage && $activePackage->package)
                                <div class="text-center mb-20 pb-20 border-bottom">
                                    <h4 class="mb-10 text-primary">{{ $activePackage->package->name }}</h4>
                                    @if($activePackage->package->price > 0)
                                        <div class="fs-20 fw-bold text-success">{{ number_format($activePackage->package->price, 0) }} руб.</div>
                                    @else
                                        <div class="fs-20 fw-bold text-muted">Бесплатно</div>
                                    @endif
                                </div>

                                {{-- Сроки действия --}}
                                <div class="mb-20 pb-20 border-bottom">
                                    <div class="mb-10">
                                        <small class="text-muted d-block mb-1">Дата активации:</small>
                                        <strong>{{ $activePackage->start_date ? \Carbon\Carbon::parse($activePackage->start_date)->format('d.m.Y') : 'N/A' }}</strong>
                                    </div>

                                    @if($activePackage->end_date)
                                        <div class="mb-10">
                                            <small class="text-muted d-block mb-1">Дата окончания:</small>
                                            <strong>{{ \Carbon\Carbon::parse($activePackage->end_date)->format('d.m.Y') }}</strong>
                                        </div>
                                        @php
                                            $daysLeft = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($activePackage->end_date), false);
                                        @endphp
                                        @if($daysLeft > 0)
                                            <div class="alert alert-info py-2 px-3 mb-0">
                                                <small><i class="uil uil-clock"></i> Осталось дней: <strong>{{ $daysLeft }}</strong></small>
                                            </div>
                                        @elseif($daysLeft == 0)
                                            <div class="alert alert-warning py-2 px-3 mb-0">
                                                <small><i class="uil uil-exclamation-triangle"></i> Истекает сегодня!</small>
                                            </div>
                                        @else
                                            <div class="alert alert-danger py-2 px-3 mb-0">
                                                <small><i class="uil uil-times-circle"></i> Тариф истёк</small>
                                            </div>
                                        @endif
                                    @else
                                        <div class="mb-10">
                                            <div class="text-success">
                                                <i class="uil uil-infinity me-1"></i>
                                                <strong>Бессрочный тариф</strong>
                                            </div>
                                        </div>
                                    @endif

                                    @if($activePackage->package->duration_days)
                                        <div class="mt-10">
                                            <small class="text-muted">Длительность тарифа: <strong>{{ $activePackage->package->duration_days }} дней</strong></small>
                                        </div>
                                    @endif
                                </div>

                                @include('components.auto-renew-toggle', [
                                    'sellerCabinetState' => $sellerCabinetState,
                                ])

                                {{-- Лимиты товаров --}}
                                <div class="mb-20 pb-20 border-bottom">
                                    <h6 class="fw-500 mb-15">Лимиты товаров</h6>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-10">
                                        <span class="text-muted">
                                            <i class="uil uil-box text-primary"></i> Активных товаров:
                                        </span>
                                        <strong>
                                            @if($activePackage->package->active_products_limit)
                                                {{ $productLimitService->activeProductsCount($seller) }} / {{ $activePackage->package->active_products_limit }}
                                            @else
                                                <span class="text-success">Безлимит</span>
                                            @endif
                                        </strong>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">
                                            <i class="uil uil-star text-warning"></i> VIP-товаров:
                                        </span>
                                        <strong>
                                            @if($activePackage->package->vip_products_limit !== null)
                                                {{ $productLimitService->vipProductsCount($seller) }} / {{ $activePackage->package->vip_products_limit }}
                                            @else
                                                <span class="text-success">Безлимит</span>
                                            @endif
                                        </strong>
                                    </div>
                                </div>

                                {{-- Возможности тарифа --}}
                                <div class="mb-20">
                                    <h6 class="fw-500 mb-15">Возможности</h6>
                                    
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item px-0 py-2 border-0">
                                            @if($activePackage->package->has_website_link)
                                                <i class="uil uil-check-circle text-success"></i>
                                                <span class="text-success">Ссылка на сайт</span>
                                            @else
                                                <i class="uil uil-times-circle text-muted"></i>
                                                <span class="text-muted">Ссылка на сайт</span>
                                            @endif
                                        </div>

                                        <div class="list-group-item px-0 py-2 border-0">
                                            @if($activePackage->package->has_social_links)
                                                <i class="uil uil-check-circle text-success"></i>
                                                <span class="text-success">Подключение соцсетей</span>
                                            @else
                                                <i class="uil uil-times-circle text-muted"></i>
                                                <span class="text-muted">Подключение соцсетей</span>
                                            @endif
                                        </div>

                                        <div class="list-group-item px-0 py-2 border-0">
                                            @if($activePackage->package->has_order_system)
                                                <i class="uil uil-check-circle text-success"></i>
                                                <span class="text-success">Прием заказов</span>
                                            @else
                                                <i class="uil uil-times-circle text-muted"></i>
                                                <span class="text-muted">Прием заказов</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Кнопка смены тарифа --}}
                                <a href="{{ route('account.packages.index') }}" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="uil uil-refresh"></i> Сменить тариф
                                </a>
                            @else
                                <div class="text-center py-4">
                                    <i class="uil uil-exclamation-triangle fs-40 text-warning mb-3"></i>
                                    <p class="text-muted mb-3">Нет активного тарифа</p>
                                    <a href="{{ route('account.packages.index') }}" class="btn btn-primary btn-sm">
                                        <i class="uil uil-plus"></i> Выбрать тариф
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
