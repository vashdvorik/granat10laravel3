{{-- Кнопки «Продолжить через …» (вход и регистрация одним кликом). Показываются только для настроенных провайдеров. --}}
@inject('socialLogin', 'App\Services\SocialLoginService')

@if($socialLogin->enabledProviders() !== [])
    <div class="flex items-center gap-x-3 mb-[20px]">
        <span class="flex-1" style="border-top: 1px solid #E8EDF2;"></span>
        <span class="text-xs text-[#8083A3]">или</span>
        <span class="flex-1" style="border-top: 1px solid #E8EDF2;"></span>
    </div>

    @foreach($socialLogin->enabledProviders() as $provider => $label)
        <a class="flex items-center justify-center border rounded-lg gap-x-[10px] border-[#E8EDF2] p-[3px] py-[14px] w-full mb-[20px]"
           href="{{ route('social.redirect', $provider) }}">
            <img src="{{ asset('auth/assets/images/icons/icon-'.$provider.'.svg') }}" alt="{{ $label }}">
            <span class="text-gray-1100 text-sm dark:text-gray-dark-1100">Продолжить через {{ $label }}</span>
        </a>
    @endforeach
@endif
