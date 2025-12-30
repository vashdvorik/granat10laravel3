{{-- Профиль пользователя --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')

{{--
<div class="contents">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-main user-member justify-content-sm-between">
                    <div class=" d-flex flex-wrap justify-content-center breadcrumb-main__wrapper">
                        <div class="d-flex align-items-center user-member__title justify-content-center me-sm-25">
                            <h4 class="text-capitalize fw-500 breadcrumb-title">Профиль пользователя</h4>
                        </div>
                    </div>
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

        <div class="row">
            <!-- Основная информация -->
            <div class="col-lg-4">
                <div class="card mb-25">
                    <div class="card-body text-center">
                        <div class="account-profile">
                            <div class="ap-img pro_img_wrapper mb-20">
                                <img class="ap-img__main rounded-circle wh-120 bg-lighter d-flex" 
                                     src="{{ asset('cabinet/img/author/profile.png') }}" 
                                     alt="profile">
                            </div>
                            <div class="ap-nameAddress pb-3">
                                <h5 class="ap-nameAddress__title">{{ $user->name }}</h5>
                                <p class="ap-nameAddress__subTitle fs-14 m-0 text-muted">
                                    {{ $user->email }}
                                </p>
                            </div>
                        </div>

                        <div class="border-top pt-20">
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-center mb-20">
                                        <p class="mb-0 text-muted fs-13">Баланс</p>
                                        <h4 class="mb-0 text-primary">{{ number_format($user->balance ?? 0, 2, '.', ' ') }} ₽</h4>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center mb-20">
                                        <p class="mb-0 text-muted fs-13">Личный телефон</p>
                                        <h6 class="mb-0">{{ $user->phone ?? 'Не указан' }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="button-group d-flex flex-wrap pt-15 mb-15 gap-2">
                            <a href="{{ route('account.user.edit') }}" class="btn btn-primary btn-default btn-squared flex-fill">
                                <i class="uil uil-edit"></i> Редактировать
                            </a>
                            <a href="{{ route('account.user.change_password') }}" class="btn btn-outline-lighten btn-squared flex-fill">
                                <i class="uil uil-lock"></i> Сменить пароль
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Детальная информация -->
            <div class="col-lg-8">
                <!-- Общая информация -->
                <div class="card mb-25">
                    <div class="card-header">
                        <h6>Личная информация</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label class="color-dark fs-14 fw-500 align-center mb-10">Имя</label>
                                    <p class="fs-15 fw-400 text-dark">{{ $user->name }}</p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label class="color-dark fs-14 fw-500 align-center mb-10">Email</label>
                                    <p class="fs-15 fw-400 text-dark">{{ $user->email }}</p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label class="color-dark fs-14 fw-500 align-center mb-10">Телефон</label>
                                    <p class="fs-15 fw-400 text-dark">{{ $user->phone ?? 'Не указан' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-25">
                                <div class="form-group">
                                    <label class="color-dark fs-14 fw-500 align-center mb-10">Дата регистрации</label>
                                    <p class="fs-15 fw-400 text-dark">{{ $user->created_at->format('d.m.Y H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Баланс и финансы -->
                <div class="card mb-25">
                    <div class="card-header">
                        <h6>Баланс</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="p-20 bg-primary-transparent radius-xl mb-20">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <p class="mb-1 text-muted fs-13">Текущий баланс</p>
                                            <h3 class="mb-0 text-primary">{{ number_format($user->balance ?? 0, 2, '.', ' ') }} ₽</h3>
                                        </div>
                                        <div>
                                            <span class="wh-50 bg-primary rounded-circle d-flex align-items-center justify-content-center">
                                                <i class="uil uil-wallet fs-24 color-white"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
            
                        </div>
                        <div class="button-group d-flex flex-wrap gap-2">
                            <a href="#" class="btn btn-success btn-squared">
                                <i class="uil uil-plus"></i> Пополнить баланс
                            </a>
                            <a href="#" class="btn btn-outline-secondary btn-squared">
                                <i class="uil uil-history"></i> История операций
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> --}}

<div class="contents">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-main">
                    <h4 class="text-capitalize breadcrumb-title">Мой профиль</h4>
                    <div class="breadcrumb-action justify-content-center flex-wrap">

                    </div>
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

        <div class="row">
            <!-- Левая боковая панель -->
            <div class="col-xxl-3 col-lg-4 col-sm-5">
                <div class="card mb-25">
                    <div class="card-body text-center p-0">
                        <!-- Профиль с аватаром -->
                        <div class="account-profile border-bottom pt-25 px-25 pb-0 flex-column d-flex align-items-center">
                            <div class="ap-img mb-20 pro_img_wrapper">
                                <img class="ap-img__main rounded-circle wh-120" 
                                     src="{{ asset('cabinet/img/author/profile.png') }}" 
                                     alt="profile">
                            </div>
                            <div class="ap-nameAddress pb-3">
                                <h5 class="ap-nameAddress__title">{{ $user->name }}</h5>
                                <p class="ap-nameAddress__subTitle fs-14 m-0">{{ $user->email }}</p>
                            </div>
                        </div>

                        <!-- Быстрые действия -->
                        <div class="p-20 pb-25">
                            <div class="button-group d-flex flex-column gap-2">
                                <a href="{{ route('account.user.edit') }}" class="btn btn-primary btn-squared">
                                    <i class="uil uil-edit"></i> Редактировать данные
                                </a>
                                <a href="{{ route('account.user.change_password') }}" class="btn btn-outline-lighten btn-squared">
                                    <i class="uil uil-lock"></i> Сменить пароль
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Правая панель -->
            <div class="col-xxl-9 col-lg-8 col-sm-7">
                
                <!-- Детальная информация -->
                <div class="row">
                    <!-- Личные данные -->
                    <div class="col-lg-6">
                        <div class="card mb-25">
                            <div class="card-header">
                                <h6 class="fw-500">Личная информация</h6>
                            </div>
                            <div class="card-body pt-20 pb-25">
                                <div class="mb-20">
                                    <div class="d-flex align-items-center mb-10">
                                        <i class="uil uil-user color-primary fs-20 me-10"></i>
                                        <span class="color-light fs-14 fw-500">Имя продавца</span>
                                    </div>
                                    <p class="fs-15 fw-500 color-dark mb-0 ms-30">{{ $user->name }}</p>
                                </div>

                                <div class="mb-20">
                                    <div class="d-flex align-items-center mb-10">
                                        <i class="uil uil-envelope color-primary fs-20 me-10"></i>
                                        <span class="color-light fs-14 fw-500">Email адрес</span>
                                    </div>
                                    <p class="fs-15 fw-500 color-dark mb-0 ms-30">{{ $user->email }}</p>
                                </div>

                                <div class="mb-20">
                                    <div class="d-flex align-items-center mb-10">
                                        <i class="uil uil-phone color-primary fs-20 me-10"></i>
                                        <span class="color-light fs-14 fw-500">Личный номер телефона</span>
                                    </div>
                                    <p class="fs-15 fw-500 color-dark mb-0 ms-30">{{ $user->phone ?? 'Не указан' }}</p>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Финансы -->
                    <div class="col-lg-6">
                        <div class="card mb-25">
                            <div class="card-header">
                                <h6 class="fw-500">Управление балансом</h6>
                            </div>
                            <div class="card-body pt-20 pb-25">
                                <!-- Баланс блок -->
                                <div class="p-20 mb-20 radius-md" 
                                     style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="mb-5 color-light fs-13">Доступно средств</p>
                                            <h2 class="mb-0 fw-600" style="color: #667eea;">{{ number_format($user->balance ?? 0, 2, '.', ' ') }} ₽</h2>
                                        </div>
                                        <div>
                                            <span class="wh-60 rounded-circle d-flex align-items-center justify-content-center" 
                                                  style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                <i class="uil uil-wallet fs-24 color-white"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Кнопки действий -->
                                <div class="button-group d-grid gap-2">
                                    <a href="#" class="btn btn-success btn-squared px-20">
                                        <i class="uil uil-plus-circle"></i> Пополнить баланс
                                    </a>
                                    <a href="#" class="btn btn-outline-secondary btn-squared px-20">
                                        <i class="uil uil-history-alt"></i> История операций
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection