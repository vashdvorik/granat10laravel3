{{-- Защита формы от ботов: скрытая ловушка, метка времени и (если заданы ключи) Cloudflare Turnstile. См. ProtectAuthForms. --}}
<div style="position: absolute; left: -10000px; top: auto; width: 1px; height: 1px; overflow: hidden;" aria-hidden="true">
    <label>Не заполняйте это поле
        <input type="text" name="contact_url" value="" tabindex="-1" autocomplete="off">
    </label>
</div>
<input type="hidden" name="form_token" value="{{ \App\Http\Middleware\ProtectAuthForms::issueToken() }}">

@if(config('auth_protection.turnstile.site_key') && config('auth_protection.turnstile.secret'))
    <div class="cf-turnstile mb-[20px]" style="display: flex; justify-content: center;"
         data-sitekey="{{ config('auth_protection.turnstile.site_key') }}" data-language="ru"></div>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
