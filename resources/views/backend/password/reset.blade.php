
@if(auth()->check())
    {{-- Если пользователь авторизован, перенаправляем в кабинет --}}
    <script>window.location = "{{ route('account.index') }}";</script>
    @php exit; @endphp
@endif

@extends('backend.auth.login_layouts')
@section('title', 'Сброс пароля | Маркетплейс ПМР')

@section('content')
<div>
<!-- Форма для сброса пароля из reset-password.html -->
<form class="rounded-2xl bg-white mx-auto p-10 text-center max-w-[440px] my-[84px] dark:bg-[#1F2128]"
      action="{{ route('password.email') }}" method="POST">
    @csrf

    <div class="mb-4 text-center mx-auto">
        <img class="inline-block" src="{{ asset('auth/assets/images/icons/icon-landing-reset-password.svg') }}" alt="landing success">
    </div>
    <h3 class="font-bold text-2xl text-gray-1100 capitalize mb-[5px] dark:text-gray-dark-1100">Сброс пароля</h3>
    <p class="text-sm text-gray-500 mb-[30px] dark:text-gray-dark-500">
        Введите ваш e-mail, и мы отправим вам инструкции по сбросу пароля.
    </p>

    @if (session('status'))
        <div class="mb-4 text-green-600 font-bold">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 text-red-500 font-bold">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div>
        <label for="email">
            <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">E-mail</p>
        </label>
        <div class="form-control mb-[20px]">
            <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442]">
                <input class="input flex-1 bg-transparent text-gray-900 focus:outline-none dark:text-gray-dark-300"
                       type="email" placeholder="Email" name="email" id="email" value="{{ old('email') }}" required autofocus>
                <button type="button" class="btn-square flex items-center justify-center bg-transparent" tabindex="-1">
                    <img src="{{ asset('auth/assets/images/icons/icon-sms.svg') }}" alt="sms icon">
                </button>
            </div>
        </div>
    </div>
    <button type="submit" class="btn normal-case h-fit min-h-fit transition-all duration-300 border-4 w-full border-neutral-bg mb-[20px] py-[14px] dark:border-dark-neutral-bg">
        Сбросить пароль
    </button>
    <a class="text-center text-xs block text-[#8083A3] mb-[20px]" href="{{ route('login') }}">Вспомнили пароль?</a>
</form>
</div>

@endsection