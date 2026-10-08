<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\RegisteredUserResponse;
use App\Http\Responses\VerifiedEmailResponse;
use App\Http\Responses\VerificationLinkSentResponse;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VerifyEmailResponse::class, VerifiedEmailResponse::class);
        $this->app->singleton(RegisterResponse::class, RegisteredUserResponse::class);
        $this->app->singleton(EmailVerificationNotificationSentResponse::class, VerificationLinkSentResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        Fortify::loginView(function () {
        return view('backend.auth.login'); 
        });

        Fortify::registerView(function () {
        return view('backend.auth.register');
        });

        // Страница «Проверьте почту» для пользователей с неподтверждённым адресом
        Fortify::verifyEmailView(function () {
            return view('backend.auth.verify-email');
        });

        Fortify::requestPasswordResetLinkView(function () {
        return view('backend.password.reset');
        });

        // Для формы ввода нового пароля (по ссылке из письма)
        Fortify::resetPasswordView(function ($request) {
        return view('backend.password.set', ['request' => $request]);
        });
    
    }
}
