@extends('backend.auth.login_layouts')
@section('title', 'Стать продавцом — BIMM')

@section('content')
{{--
@if(auth()->check())
    <!-- Если пользователь авторизован, перенаправляем в кабинет -->
    <script>window.location = "{{ route('account.index') }}";</script>
    @php exit; @endphp
@endif
--}}
<div>
    <form class="rounded-2xl bg-white mx-auto p-10 text-center max-w-[440px] mt-0 mb-6 md:my-[84px] dark:bg-[#1F2128]"
          action="{{ route('register') }}" method="POST">
        @csrf

        <div class="mb-4 text-center mx-auto">
        <!--    <img class="inline-block" src="{{ asset('auth/assets/images/icons/icon-landing-success-1.svg') }}" alt="landing success"> -->
        </div>
        <h3 class="font-bold text-2xl text-gray-1100 capitalize mb-[5px] dark:text-gray-dark-1100">Стать продавцом</h3>
        <p class="text-sm text-gray-500 mb-[30px] dark:text-gray-dark-500">Вы создаете аккаунт продавца</p>

        @if ($errors->any())
            <div style="background-color: #FEE2E2; border: 1px solid #FCA5A5; border-radius: 8px; padding: 16px; margin-bottom: 16px; text-align: left;">
                <div style="display: flex; align-items: flex-start;">
                    <svg style="width: 20px; height: 20px; color: #DC2626; margin-right: 12px; margin-top: 2px; flex-shrink: 0;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div style="flex: 1;">
                        <p style="font-weight: 600; color: #991B1B; margin-bottom: 8px; margin-top: 0;">Обнаружены ошибки:</p>
                        <ul style="list-style-type: disc; padding-left: 20px; margin: 0;">
                            @foreach ($errors->all() as $error)
                                <li style="color: #B91C1C; font-size: 14px; margin-bottom: 4px;">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div>
            <label for="name">
                <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">Введите имя и фамилию предпринимателя</p>
            </label>
            <div class="form-control mb-[20px]">
                <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442]">
                    <input class="input flex-1 bg-transparent text-gray-900 focus:outline-none dark:text-gray-dark-300"
                           type="text" placeholder="Имя и фамилия" name="name" id="name" value="{{ old('name') }}" required>
                    <button type="button" class="btn-square flex items-center justify-center bg-transparent" tabindex="-1">
                        <img src="{{ asset('auth/assets/images/icons/icon-input-user.svg') }}" alt="user icon">
                    </button>
                </div>
            </div>

            <label for="email">
                <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">Почта</p>
            </label>
            <div class="form-control mb-[20px]">
                <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442]">
                    <input class="input flex-1 bg-transparent text-gray-900 focus:outline-none dark:text-gray-dark-300"
                           type="email" placeholder="Email" name="email" id="email" value="{{ old('email') }}" required>
                    <button type="button" class="btn-square flex items-center justify-center bg-transparent" tabindex="-1">
                        <img src="{{ asset('auth/assets/images/icons/icon-sms.svg') }}" alt="sms icon">
                    </button>
                </div>
            </div>

            <label for="password">
                <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">Пароль</p>
            </label>
            <div class="form-control mb-[20px]">
                <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442]">
                    <input id="password-input" class="input flex-1 bg-transparent text-gray-900 focus:outline-none dark:text-gray-dark-300"
                           type="password" placeholder="Пароль" name="password" required autocomplete="new-password">
                    <button type="button" id="toggle-password" class="btn-square border-white flex items-center justify-center bg-transparent" tabindex="-1">
                        <img src="{{ asset('auth/assets/images/icons/icon-eye.svg') }}" alt="eye icon">
                    </button>
                </div>
            </div>

            <label for="password_confirmation">
                <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">Подтвердите пароль</p>
            </label>
            <div class="form-control mb-[20px]">
                <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442]">
                    <input id="password-confirmation-input" class="input flex-1 bg-transparent text-gray-900 focus:outline-none dark:text-gray-dark-300"
                           type="password" placeholder="Пароль" name="password_confirmation" required autocomplete="new-password">
                    <button type="button" id="toggle-password-confirmation" class="btn-square border-white flex items-center justify-center bg-transparent" tabindex="-1">
                        <img src="{{ asset('auth/assets/images/icons/icon-eye.svg') }}" alt="eye icon">
                    </button>
                </div>
            </div>
        </div>
        
        @include('backend.auth.partials.form-protection')
        <button type="submit" class="btn normal-case h-fit min-h-fit transition-all duration-300 border-4 w-full border-neutral-bg mb-[20px] py-[14px] dark:border-dark-neutral-bg">Создать кабинет продавца</button>
        @include('backend.auth.partials.social-login')
        <p class="text-sm text-gray-1100 dark:text-gray-dark-1100">
            Уже есть кабинет продавца?
            <a class="text-color-brands" href="{{ route('login') }}">Войти продавцу</a>
        </p>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Показать/скрыть пароль
    const btn = document.getElementById('toggle-password');
    const input = document.getElementById('password-input');
    if(btn && input) {
        btn.addEventListener('click', function() {
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    }
    // Показать/скрыть подтверждение пароля
    const btn2 = document.getElementById('toggle-password-confirmation');
    const input2 = document.getElementById('password-confirmation-input');
    if(btn2 && input2) {
        btn2.addEventListener('click', function() {
            input2.type = input2.type === 'password' ? 'text' : 'password';
        });
    }
});
</script>


@endsection
