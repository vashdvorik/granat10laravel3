{{-- Профиль пользователя --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
<div class="contents">
    <div class="container-fluid">
        @include('backend.cabinet.partials.flash')
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-main">
                    <h4 class="breadcrumb-title">Мой профиль</h4>
                </div>
            </div>
        </div>



        <div class="row g-4">
            <div class="col-xxl-4 col-lg-5">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="account-profile border-bottom pb-4">
                            <div class="ap-img pro_img_wrapper mb-3">
                                <img class="ap-img__main rounded-circle wh-120" src="{{ asset('cabinet/img/author/profile.png') }}" alt="profile">
                            </div>
                            <div class="ap-nameAddress">
                                <h5 class="ap-nameAddress__title mb-1">{{ $user->name }}</h5>
                                <p class="ap-nameAddress__subTitle fs-14 m-0">{{ $user->email }}</p>
                            </div>
                        </div>

                        <div class="d-grid gap-2 pt-4">
                            <a href="{{ route('account.user.edit') }}" class="btn btn-primary btn-squared">
                                <i class="uil uil-edit"></i> Редактировать данные
                            </a>
                            <a href="{{ route('account.user.change_password') }}" class="btn btn-outline-secondary btn-squared">
                                <i class="uil uil-lock"></i> Сменить пароль
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-8 col-lg-7">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="fw-500 mb-0">Личная информация</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <small class="text-muted d-block mb-1">Имя продавца</small>
                                    <strong class="d-block">{{ $user->name }}</strong>
                                </div>
                                <div class="mb-4">
                                    <small class="text-muted d-block mb-1">Email адрес</small>
                                    <strong class="d-block">{{ $user->email }}</strong>
                                </div>
                                <div class="mb-0">
                                    <small class="text-muted d-block mb-1">Личный номер телефона</small>
                                    <strong class="d-block">{{ $user->phone ?? 'Не указан' }}</strong>
                                </div>
                                @unless($user->hasPassword())
                                    <div class="alert alert-info mt-4 mb-0">
                                        Вы входите через Google. Задайте пароль, чтобы можно было входить и по почте.
                                        <a href="{{ route('account.user.change_password') }}" class="fw-bold d-block mt-1">Задать пароль</a>
                                    </div>
                                @endunless
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="fw-500 mb-0">Управление балансом</h6>
                            </div>
                            <div class="card-body">
                                <div class="cabinet-balance-card p-4 mb-4">
                                    <div class="d-flex justify-content-between align-items-center gap-3">
                                        <div>
                                            <p class="mb-2 text-muted">Доступно средств</p>
                                            <div class="cabinet-balance-card__amount">
                                                {{ number_format($user->balance ?? 0, 2, '.', ' ') }} ₽
                                            </div>
                                        </div>
                                        <span class="cabinet-balance-card__icon">
                                            <i class="uil uil-wallet fs-24 text-white"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <a href="{{ route('account.user.balance.topup') }}" class="btn btn-success btn-squared">
                                        <i class="uil uil-plus-circle"></i> Пополнить баланс
                                    </a>
                                    <a href="{{ route('account.balance.index') }}" class="btn btn-outline-secondary btn-squared">
                                        <i class="uil uil-history-alt"></i> История операций
                                    </a>
                                </div>

                                @include('backend.cabinet.user.partials.developer-tip')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
