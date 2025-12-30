{{-- Профиль пользователя Акаунт/профиль /account/user--}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')

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