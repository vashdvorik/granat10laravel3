{{-- Редактирование профиля пользователя  --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')

<div class="contents">
    <div class="container-fluid">
        @include('backend.cabinet.partials.flash')
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-main">
                    <h4 class="breadcrumb-title">Редактирование профиля</h4>
                    <div class="breadcrumb-action justify-content-center flex-wrap">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">

                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>


        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="uil uil-exclamation-triangle"></i>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-xxl-8 col-lg-10">
                <!-- Edit Profile -->
                <div class="edit-profile mt-25">
                    <div class="card">
                        <div class="card-header px-sm-25 px-3">
                            <div class="edit-profile__title">
                                <h6>Личные данные</h6>
                                <span class="fs-13 color-light fw-400">Обновите вашу персональную информацию</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row justify-content-center">
                                <div class="col-xxl-9">
                                    <div class="edit-profile__body mx-xl-20">
                                        <form action="{{ route('account.user.update') }}" method="POST">
                                            @csrf
                                            @method('PUT')

                                            <!-- Имя -->
                                            <div class="form-group mb-25">
                                                <label for="name" class="color-dark fs-14 fw-500 align-center mb-10">
                                                    <i class="uil uil-user color-primary me-5"></i>
                                                    Имя продавца <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" 
                                                       class="form-control form-control-lg @error('name') is-invalid @enderror" 
                                                       id="name" 
                                                       name="name"
                                                       value="{{ old('name', $user->name) }}"
                                                       placeholder="Введите ваше имя"
                                                       required>
                                                @error('name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Email -->
                                            <div class="form-group mb-25">
                                                <label for="email" class="color-dark fs-14 fw-500 align-center mb-10">
                                                    <i class="uil uil-envelope color-primary me-5"></i>
                                                    Email адрес <span class="text-danger">*</span>
                                                </label>
                                                <input type="email" 
                                                       class="form-control form-control-lg @error('email') is-invalid @enderror" 
                                                       id="email" 
                                                       name="email"
                                                       value="{{ old('email', $user->email) }}"
                                                       placeholder="example@mail.com"
                                                       required>
                                                @error('email')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="form-text text-muted mt-2">
                                                    <i class="uil uil-info-circle"></i> Email будет использоваться для восстановления пароля.
                                                </small>
                                            </div>

                                            <!-- Телефон -->
                                            <div class="form-group mb-25">
                                                <label for="phone" class="color-dark fs-14 fw-500 align-center mb-10">
                                                    <i class="uil uil-phone color-primary me-5"></i>
                                                    Личный номер телефона
                                                </label>
                                                <input type="text" 
                                                       class="form-control form-control-lg @error('phone') is-invalid @enderror" 
                                                       id="phone" 
                                                       name="phone"
                                                       value="{{ old('phone', $user->phone) }}"
                                                       placeholder="(777) 111-77">
                                                @error('phone')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="form-text text-muted mt-2">
                                                    <i class="uil uil-info-circle"></i> Для связи с вами администрации маркетплейса.
                                                </small>
                                            </div>

                                            <!-- Информационная панель -->
                                            <div class="alert alert-info-10 border-info alert-dismissible fade show mb-25" role="alert">
                                                <div class="alert-content">
                                                    <div class="d-flex align-items-center">
                                                        <i class="uil uil-shield-check fs-24 me-15"></i>
                                                        <div>
                                                            <h6 class="mb-1">Безопасность данных</h6>
                                                            <p class="mb-0 fs-13">Ваши персональные данные надежно защищены. Мы не передаем их третьим лицам, и никогда не используем для рекламных предложений.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Кнопки действий -->
                                            <div class="button-group d-flex flex-wrap pt-30 mb-15 gap-10">
                                                <button type="submit" class="btn btn-primary btn-default btn-squared px-30">
                                                    <i class="uil uil-check-circle"></i> Сохранить изменения
                                                </button>
                                                <a href="{{ route('account.user.index') }}" class="btn btn-light btn-default btn-squared fw-400 px-30">
                                                    <i class="uil uil-times-circle"></i> Отмена
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Edit Profile End -->

                
            </div>
        </div>
    </div>
</div>

@endsection