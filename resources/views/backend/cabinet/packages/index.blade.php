{{-- Страница тарифов в магазине --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')

<div class="contents">
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

        <!-- Информация о балансе -->
        <div class="row mb-30">
            <div class="col-12">
                <div class="card card-default card-md">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Ваш баланс</h6>
                                <h4 class="mb-0">{{ number_format(auth()->user()->balance ?? 0, 0, '.', ' ') }} ₽</h4>
                            </div>
                            <div>
                                <a href="#" class="btn btn-success btn-squared">Пополнить баланс</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Карточки тарифов -->
        <div class="row">
            @foreach($packages as $package)
                @php
                    $isCurrentPackage = $currentPackage && $currentPackage->package_id == $package->id;
                @endphp
                
                <div class="col-xxl-3 col-lg-4 col-sm-6 mb-30">
                    <div class="card h-100">
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
                        </div>
                        
                        <div class="price_action d-flex pb-30 ps-30">
                            @if($isCurrentPackage)
                                <button class="btn btn-default btn-squared color-light btn-outline-light text-capitalize px-30 color-gray fw-500" disabled>
                                    Текущий тариф
                                </button>
                            @else
                                <form action="{{ route('account.packages.purchase', $package) }}" method="POST" class="w-100 package-purchase-form" data-package-name="{{ $package->name }}" data-package-price="{{ $package->price }}">
                                    @csrf
                                    <button type="button" class="btn btn-primary btn-squared text-capitalize px-30 fw-500 purchase-btn">
                                        {{ $package->price == 0 ? 'Выбрать' : 'Купить за ' . number_format($package->price, 0, '.', ' ') . ' ₽' }}
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
                    confirmButtonText: 'Пополнить баланс',
                    showCancelButton: true,
                    cancelButtonText: 'Закрыть',
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Перенаправление на страницу пополнения баланса
                        window.location.href = '#'; // Замените на реальный URL
                    }
                });
                return;
            }
            
            // Подтверждение покупки
            const priceText = packagePrice == 0 ? 'бесплатно' : `${packagePrice.toLocaleString()} ₽`;
            
            Swal.fire({
                title: 'Подтверждение покупки',
                html: `
                    <p>Вы уверены, что хотите активировать тариф</p>
                    <h4 class="mb-3">${packageName}</h4>
                    <p>Стоимость: <strong>${priceText}</strong></p>
                    ${packagePrice > 0 ? `<p>Баланс после покупки: <strong>${(userBalance - packagePrice).toLocaleString()} ₽</strong></p>` : ''}
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#5046e4',
                cancelButtonColor: '#6c757d',
                confirmButtonText: packagePrice == 0 ? 'Да, выбрать' : 'Да, купить',
                cancelButtonText: 'Отмена',
                reverseButtons: true
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