{{-- Пользователь. Смена пароля / задание пароля (для вошедших через Google) --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
@php($hasPassword = auth()->user()->hasPassword())
<div class="contents">
    <div class="container-fluid">
        @include('backend.cabinet.partials.flash')
        <div class="row">
            <div class="col-12">
                <div class="breadcrumb-main">
                    <h4 class="breadcrumb-title">{{ $hasPassword ? 'Смена пароля' : 'Задать пароль' }}</h4>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xxl-6 col-lg-8">
                <div class="card">
                    <div class="card-header px-sm-25 px-3">
                        <div class="edit-profile__title">
                            <h6>{{ $hasPassword ? 'Смена пароля' : 'Пароль для входа по почте' }}</h6>
                            <span class="fs-13 color-light fw-400">
                                {{ $hasPassword ? 'Измените пароль аккаунта.' : 'Вы входите через Google, поэтому пароля у вас пока нет.' }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        @unless($hasPassword)
                            <div class="alert alert-info mb-4">
                                Задайте пароль, и вы сможете входить двумя способами: через Google в один клик
                                или по почте <strong>{{ auth()->user()->email }}</strong> и паролю.
                                Текущий пароль вводить не нужно.
                            </div>
                        @endunless

                        <form method="POST" action="{{ route('account.user.update_password') }}">
                            @csrf

                            @if($hasPassword)
                                <div class="form-group mb-20">
                                    <label for="current_password">Текущий пароль</label>
                                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required>
                                    @error('current_password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            <div class="form-group mb-20">
                                <label for="password">{{ $hasPassword ? 'Новый пароль' : 'Пароль' }}</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Пароль" required>
                                    <span class="uil uil-eye-slash text-lighten fs-15 field-icon toggle-password cabinet-password-toggle"></span>
                                </div>
                                <small class="text-light fs-13">Минимум 8 символов</small>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-1">
                                <label for="password_confirmation">Подтверждение пароля</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" placeholder="Подтвердите пароль" required>
                                    <span class="uil uil-eye-slash text-lighten fs-15 field-icon toggle-password2 cabinet-password-toggle"></span>
                                </div>
                                @error('password_confirmation')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex flex-wrap gap-2 pt-4">
                                <button type="submit" class="btn btn-primary btn-squared">
                                    {{ $hasPassword ? 'Сохранить изменения' : 'Задать пароль' }}
                                </button>
                                <a href="{{ route('account.user.index') }}" class="btn btn-outline-secondary btn-squared">
                                    Отмена
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
   document.querySelectorAll('.toggle-password, .toggle-password2').forEach(function(toggle) {
      toggle.addEventListener('click', function() {
         let input = this.previousElementSibling;
         if (input.type === 'password') {
            input.type = 'text';
            this.classList.remove('uil-eye-slash');
            this.classList.add('uil-eye');
         } else {
            input.type = 'password';
            this.classList.add('uil-eye-slash');
            this.classList.remove('uil-eye');
         }
      });
   });
</script>
@endsection
