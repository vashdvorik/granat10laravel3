@extends('backend.auth.login_layouts')
@section('title', 'Подтвердите почту — BIMM')

@section('content')
<div>
    <div class="rounded-2xl bg-white mx-auto p-10 text-center max-w-[440px] mt-0 mb-6 md:my-[84px] dark:bg-[#1F2128]">
        <h3 class="font-bold text-2xl text-gray-1100 mb-[5px] dark:text-gray-dark-1100">Подтвердите почту</h3>
        <p class="text-sm text-gray-500 mb-[24px] dark:text-gray-dark-500">
            Мы отправили письмо со ссылкой на адрес
            <strong class="text-gray-1100 dark:text-gray-dark-1100">{{ auth()->user()->email }}</strong>.
            Перейдите по ссылке, чтобы открыть кабинет продавца.
        </p>

        @if (session('status') === 'verification-link-sent')
            <div style="background-color: #DCFCE7; border: 1px solid #86EFAC; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; text-align: left; color: #166534; font-size: 14px;">
                Новое письмо отправлено. Проверьте почту, а также папку «Спам».
            </div>
        @endif

        @if (session('status') === 'verification-link-failed')
            <div style="background-color: #FEF3C7; border: 1px solid #FCD34D; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; text-align: left; color: #92400E; font-size: 14px;">
                Не удалось отправить письмо: почтовый сервер временно недоступен. Подождите минуту и нажмите «Отправить письмо ещё раз».
            </div>
        @endif

        @if ($errors->any())
            <div style="background-color: #FEE2E2; border: 1px solid #FCA5A5; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; text-align: left; color: #B91C1C; font-size: 14px;">
                @foreach ($errors->all() as $error)
                    <p style="margin: 0;">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn normal-case h-fit min-h-fit transition-all duration-300 border-4 w-full border-neutral-bg mb-[16px] py-[14px] dark:border-dark-neutral-bg">
                Отправить письмо ещё раз
            </button>
        </form>

        <p class="text-xs text-[#8083A3] mb-[20px]">
            Письмо не пришло? Проверьте, что адрес указан без ошибок. Если нет — выйдите и зарегистрируйтесь заново с верным адресом.
        </p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-color-brands" style="background: none; border: 0; cursor: pointer;">Выйти</button>
        </form>
    </div>
</div>
@endsection
