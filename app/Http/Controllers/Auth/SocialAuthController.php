<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\SocialLoginException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SocialLoginService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Features;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/** Вход и регистрация одним кликом через внешний аккаунт (Google). */
class SocialAuthController extends Controller
{
    public function __construct(private readonly SocialLoginService $social) {}

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless($this->social->isEnabled($provider), 404);

        return Socialite::driver($provider)
            ->redirectUrl(route('social.callback', $provider))
            ->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless($this->social->isEnabled($provider), 404);

        $label = SocialLoginService::PROVIDERS[$provider];

        if ($request->filled('error')) {
            return $this->failed("Вход через {$label} отменён.");
        }

        try {
            $socialUser = Socialite::driver($provider)
                ->redirectUrl(route('social.callback', $provider))
                ->user();
        } catch (Throwable $e) {
            Log::warning('Social login failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return $this->failed("Не удалось войти через {$label}. Попробуйте ещё раз или войдите по паролю.");
        }

        try {
            [$user, $created] = $this->social->resolveUser($provider, $socialUser);
        } catch (SocialLoginException $e) {
            return $this->failed($e->getMessage());
        }

        if ($created) {
            event(new Registered($user)); // журнал активности; письмо не уйдёт — почта уже подтверждена провайдером
        }

        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['provider' => $provider, 'created' => $created, 'ip' => $request->ip()])
            ->log("Вход через {$label}");

        return $this->signIn($request, $user);
    }

    private function signIn(Request $request, User $user): RedirectResponse
    {
        // Если у пользователя включена двухфакторная защита — не обходим её, а передаём в стандартный челлендж Fortify
        if (Features::enabled(Features::twoFactorAuthentication())
            && in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user), true)
            && filled($user->two_factor_secret)
            && $user->two_factor_confirmed_at !== null) {
            $request->session()->put(['login.id' => $user->getKey(), 'login.remember' => true]);

            return redirect()->route('two-factor.login');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('account.index'));
    }

    private function failed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
