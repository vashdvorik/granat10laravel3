{{-- Страница тарифов в магазине --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')

<div class="contents package-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-main">
                    <h4 class="text-capitalize breadcrumb-title">Тарифные планы</h4>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="package-page-hero">
            <div>
                <span class="package-page-hero__eyebrow">Тарифы магазина</span>
                <h5>Выберите тариф, который подходит текущему количеству товаров.</h5>
                <p>Если новый тариф меньше текущего набора товаров, кабинет заранее покажет, что нужно подготовить. Товары не удаляются — лишние позиции только снимаются с публикации.</p>
            </div>
            <div class="package-page-hero__balance">
                <span>Баланс</span>
                <strong>{{ number_format(auth()->user()->balance ?? 0, 0, '.', ' ') }} ₽</strong>
                <small class="package-page-hero__balance-note">Пополнение пока не подключено</small>
            </div>
        </div>

        <!-- Карточки тарифов -->
        <div class="row">
            @foreach($packages as $package)
                @php
                    $isCurrentPackage = $currentPackage && $currentPackage->package_id == $package->id;
                    $compatibility = $packageCompatibilities[$package->id] ?? null;
                @endphp
                
                <div class="col-xxl-3 col-lg-4 col-sm-6 mb-30">
                    <div class="card h-100 package-card package-card--{{ $compatibility?->status ?? 'unknown' }} {{ $isCurrentPackage ? 'package-card--current' : '' }}">
                        <div class="card-body p-30">
                            <div class="pricing d-flex align-items-center">
                                <span class="pricing__tag {{ $isCurrentPackage ? 'color-success order-bg-opacity-success' : 'color-primary order-bg-opacity-primary' }} rounded-pill">
                                    {{ $package->name }}
                                </span>
                            </div>
                            
                            <div class="pricing__price rounded">
                                <p class="pricing_value display-3 color-dark d-flex align-items-center text-capitalize fw-600 mb-1">
                                    @if($package->price == 0)
                                        Бесплатно
                                    @else
                                        {{ number_format($package->price, 0, '.', ' ') }}<span class="fs-6 ms-2">₽</span>
                                    @endif
                                </p>
                                <p class="pricing_subtitle mb-0">
                                    {{ $package->duration_days ? $package->duration_days . ' дней' : 'Бессрочно' }}
                                </p>
                            </div>
                            
                            <div class="pricing__features">
                                <ul>
                                    <li>
                                        <span class="fa fa-check"></span>
                                        {{ $package->active_products_limit ?? 'Безлимит' }} товаров
                                    </li>
                                    <li>
                                        <span class="fa {{ $package->vip_products_limit > 0 || $package->vip_products_limit === null ? 'fa-check' : 'fa-times text-danger' }}"></span>
                                        {{ $package->vip_products_limit ?? 'Безлимит' }} VIP товаров
                                    </li>
                                    <li>
                                        <span class="fa fa-check"></span>
                                        Статистика просмотров
                                    </li>
                                    <li>
                                        <span class="fa {{ $package->has_social_links ? 'fa-check' : 'fa-times text-danger' }}"></span>
                                        Подключение соцсетей
                                    </li>
                                    <li>
                                        <span class="fa {{ $package->has_website_link ? 'fa-check' : 'fa-times text-danger' }}"></span>
                                        Размещение ссылки на сайт
                                    </li>
                                    <li>
                                        <span class="fa {{ $package->has_order_system ? 'fa-check' : 'fa-times text-danger' }}"></span>
                                        Прием заказов
                                    </li>
                                </ul>
                            </div>

                            @if($compatibility)
                                <div class="mt-3 pt-3 border-top package-compatibility">
                                    <small class="text-muted d-block mb-2">Совместимость с магазином</small>

                                    @if($compatibility->status === 'available')
                                        <div class="alert alert-success py-2 px-3 mb-2">
                                            Тариф можно подключить сейчас.
                                        </div>
                                    @elseif($compatibility->status === 'current')
                                        <div class="alert alert-info py-2 px-3 mb-2">
                                            Это текущий тариф магазина.
                                        </div>
                                    @elseif($compatibility->status === 'feature_downgrade')
                                        <div class="alert alert-warning py-2 px-3 mb-2">
                                            Тариф можно подключить, но часть функций будет недоступна.
                                        </div>
                                    @elseif($compatibility->status === 'needs_preparation')
                                        <div class="alert alert-warning py-2 px-3 mb-2">
                                            Перед переходом нужно подготовить товары.
                                        </div>
                                    @elseif($compatibility->status === 'insufficient_balance')
                                        <div class="alert alert-danger py-2 px-3 mb-2">
                                            Пополните баланс, чтобы подключить тариф.
                                        </div>
                                    @endif
                                    @php
                                        $impactLabels = [];

                                        if ($compatibility->willDisableWebsite) {
                                            $impactLabels[] = 'Указанный сайт будет скрыт';
                                        }

                                        if ($compatibility->willDisableSocialLinks) {
                                            $impactLabels[] = 'Указанные соцсети будут скрыты';
                                        }

                                        if ($compatibility->willDisableOrders) {
                                            $impactLabels[] = 'Приём заказов отключится';
                                        }
                                    @endphp

                                    @if($impactLabels !== [])
                                        <div class="package-compatibility__impact mb-2">
                                            <small class="text-muted d-block mb-2">Что изменится</small>
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach($impactLabels as $impactLabel)
                                                    <span class="badge bg-light text-dark border">{{ $impactLabel }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <ul class="mb-0 ps-3 small">
                                        <li>
                                            Активные товары:
                                            {{ $compatibility->activeProductsCount }}
                                            @if($compatibility->activeProductsLimit !== null)
                                                / {{ $compatibility->activeProductsLimit }}
                                            @else
                                                / безлимит
                                            @endif
                                        </li>
                                        <li>
                                            VIP:
                                            {{ $compatibility->vipProductsCount }}
                                            @if($compatibility->vipProductsLimit !== null)
                                                / {{ $compatibility->vipProductsLimit }}
                                            @else
                                                / безлимит
                                            @endif
                                        </li>
                                    </ul>



                                    @if($compatibility->warnings !== [])
                                        <div class="mt-2">
                                            @foreach($compatibility->warnings as $warning)
                                                <small class="text-warning d-block">{{ $warning }}</small>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        
                        <div class="price_action d-flex pb-30 ps-30 package-card__action">
                            @if(!$seller)
                                <a href="{{ route('account.store.create') }}" class="btn btn-primary btn-squared text-capitalize px-30 fw-500">
                                    Сначала создать магазин
                                </a>
                            @elseif($isCurrentPackage)
                                <button class="btn btn-default btn-squared color-light btn-outline-light text-capitalize px-30 color-gray fw-500" disabled>
                                    Текущий тариф
                                </button>
                            @elseif($compatibility && $compatibility->status === 'needs_preparation')
                                <a href="{{ route('account.packages.prepare', $package) }}" class="btn btn-outline-warning btn-squared text-capitalize px-30 fw-500">
                                    {{ $compatibility->actionLabel }}
                                </a>
                            @elseif($compatibility && $compatibility->status === 'insufficient_balance')
                                <button class="btn btn-outline-secondary btn-squared text-capitalize px-30 fw-500" disabled>
                                    {{ $compatibility->actionLabel }}
                                </button>
                            @else
                                <form action="{{ route('account.packages.purchase', $package) }}" method="POST" class="w-100 package-purchase-form" data-package-name="{{ $package->name }}" data-package-price="{{ $package->price }}">
                                    @csrf
                                    <button type="button" class="btn btn-primary btn-squared text-capitalize px-30 fw-500 purchase-btn">
                                        {{ $compatibility?->actionLabel ?? ($package->price == 0 ? 'Выбрать' : 'Купить за ' . number_format($package->price, 0, '.', ' ') . ' ₽') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div><!-- End: .card -->
                </div><!-- End: .col -->
            @endforeach
        </div><!-- End: .row -->
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentPackageName = @json($currentPackage ? $packages->firstWhere('id', $currentPackage->package_id)?->name : null);
    const currentPackagePrice = @json($currentPackage ? ($packages->firstWhere('id', $currentPackage->package_id)?->price ?? 0) : 0);

    // Обработчик для всех кнопок покупки
    document.querySelectorAll('.purchase-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const form = this.closest('form');
            const packageName = form.dataset.packageName;
            const packagePrice = parseFloat(form.dataset.packagePrice);
            const userBalance = {{ auth()->user()->balance ?? 0 }};
            
            // Проверка баланса
            if (packagePrice > 0 && userBalance < packagePrice) {
                Swal.fire({
                    icon: 'error',
                    title: 'Недостаточно средств',
                    html: `
                        <p>Для покупки тарифа <strong>${packageName}</strong> необходимо <strong>${packagePrice.toLocaleString()} ₽</strong></p>
                        <p>Ваш баланс: <strong>${userBalance.toLocaleString()} ₽</strong></p>
                        <p>Не хватает: <strong>${(packagePrice - userBalance).toLocaleString()} ₽</strong></p>
                    `,
                    confirmButtonText: 'Понятно',
                    confirmButtonColor: '#5046e4'
                });
                return;
            }
            
            // Подтверждение покупки
            const priceText = packagePrice == 0 ? 'бесплатно' : `${packagePrice.toLocaleString()} ₽`;
            const cancelWarning = (currentPackageName && currentPackagePrice > 0)
                ? `<p class="package-change-warning">⚠️ Ваш текущий тариф <strong>${currentPackageName}</strong> будет аннулирован без возврата средств.</p>`
                : '';

            Swal.fire({
                title: 'Подтверждение покупки',
                html: `
                    <p>Вы уверены, что хотите активировать тариф</p>
                    <h4 class="mb-3">${packageName}</h4>
                    <p>Стоимость: <strong>${priceText}</strong></p>
                    ${cancelWarning}
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#5046e4',
                cancelButtonColor: '#6c757d',
                confirmButtonText: packagePrice == 0 ? 'Да, выбрать' : 'Да, купить',
                cancelButtonText: 'Отмена',
                reverseButtons: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Показываем индикатор загрузки
                    Swal.fire({
                        title: 'Обработка...',
                        text: 'Активация тарифа',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Отправляем форму
                    form.submit();
                }
            });
        });
    });
});
</script>
@endpush
