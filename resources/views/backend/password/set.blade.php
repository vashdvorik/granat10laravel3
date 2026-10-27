{{--
@if(auth()->check())
     Если пользователь авторизован, перенаправляем в кабинет 
    <script>window.location = "{{ route('account.index') }}";</script>
    @php exit; @endphp
@endif
--}}
@extends('backend.auth.login_layouts')
@section('title', 'Сброс пароля — BIMM')

@section('content')
<!-- Форма восстановления пароля из reset-password.html -->
<div>
    <form class="rounded-2xl bg-white mx-auto p-10 text-center max-w-[440px] my-[84px] dark:bg-[#1F2128]" action="{{ route('password.update') }}" method="POST">
        @csrf

        <div class="mb-4 text-center mx-auto">
            <img class="inline-block" src="{{ asset('auth/assets/images/icons/icon-password-required.svg') }}" alt="landing success">
        </div>
        <h3 class="font-bold text-2xl text-gray-1100 mb-[5px] dark:text-gray-dark-1100">Сброс пароля</h3>
        <p class="text-sm text-gray-500 mb-[30px] dark:text-gray-dark-500">
            Введите новый пароль для доступа к странице.
        </p>

        @if ($errors->any())
            <div class="mb-4 text-red-500 font-bold">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div>
        <input type="hidden" name="token" value="{{ request()->route('token') }}">
        <input type="hidden" name="email" value="{{ request('email') }}">
            <label for="password">
                <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">Новый пароль</p>
            </label>
            <div class="form-control mb-[20px]">
                <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442]">
                    <input class="input flex-1 bg-transparent text-gray-900 focus:outline-none dark:text-gray-dark-300"
                           type="password" placeholder="Пароль" name="password" id="password-input" required autocomplete="new-password">
                    <button type="button" id="toggle-password" class="btn-square border-white flex items-center justify-center bg-transparent">
                        <img src="{{ asset('auth/assets/images/icons/icon-eye.svg') }}" alt="eye icon">
                    </button>
                </div>
            </div>
            <div class="mt-[20px]">
                <label for="password_confirmation">
                    <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">Подтверждение пароля</p>
                </label>
                <div class="form-control mb-[20px]">
                    <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442]">
                        <input
                            class="input flex-1 bg-transparent text-gray-900 focus:outline-none dark:text-gray-dark-300"
                            type="password"
                            placeholder="Повторите пароль"
                            name="password_confirmation"
                            id="password-confirmation-input"
                            required
                            autocomplete="new-password"
                        >
                        <button
                            type="button"
                            id="toggle-password-confirmation"
                            class="btn-square border-white flex items-center justify-center bg-transparent"
                        >
                            <img src="{{ asset('auth/assets/images/icons/icon-eye.svg') }}" alt="eye icon">
                        </button>
                    </div>
                </div>
            </div>

        </div>
        <button type="submit" class="btn normal-case h-fit min-h-fit transition-all duration-300 border-4 w-full border-neutral-bg mb-[20px] py-[14px] dark:border-dark-neutral-bg">Подтвердить</button>
        <a class="text-center text-xs block text-[#8083A3] mb-[20px]" href="{{ route('login') }}">Вспомнили пароль?</a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('toggle-password');
    const input = document.getElementById('password-input');
    if(btn && input) {
        btn.addEventListener('click', function() {
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    }
});
</script>


@endsection

