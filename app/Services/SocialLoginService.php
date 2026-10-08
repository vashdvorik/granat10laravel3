<?php

namespace App\Services;

use App\Exceptions\SocialLoginException;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Вход и регистрация одним кликом через внешний аккаунт (Google).
 *
 * Правила безопасности:
 *  - берём только адрес, который провайдер сам подтвердил (email_verified);
 *  - если на этот адрес уже есть аккаунт, но почта в нём НЕ подтверждена, пароль сбрасывается:
 *    иначе тот, кто заранее «зарегистрировал» чужой адрес, остался бы со своим паролем (захват аккаунта).
 */
class SocialLoginService
{
    /** @var array<string, string> провайдер => название на кнопке */
    public const PROVIDERS = ['google' => 'Google'];

    /**
     * Провайдеры, для которых заданы ключи приложения: кнопки показываются только для них.
     *
     * @return array<string, string>
     */
    public function enabledProviders(): array
    {
        return array_filter(
            self::PROVIDERS,
            fn (string $label, string $provider): bool => $this->isEnabled($provider),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public function isEnabled(string $provider): bool
    {
        return array_key_exists($provider, self::PROVIDERS)
            && filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"));
    }

    /**
     * @return array{0: User, 1: bool} пользователь и признак «аккаунт создан только что»
     *
     * @throws SocialLoginException
     */
    public function resolveUser(string $provider, SocialiteUser $social): array
    {
        $providerUserId = trim((string) $social->getId());
        $email = Str::lower(trim((string) $social->getEmail()));
        $label = self::PROVIDERS[$provider] ?? $provider;

        if ($providerUserId === '') {
            throw new SocialLoginException("Не удалось получить данные аккаунта {$label}.");
        }

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new SocialLoginException("Аккаунт {$label} не передал адрес почты. Войдите по паролю.");
        }

        if (! $this->providerConfirmedEmail($social)) {
            throw new SocialLoginException("Адрес почты в аккаунте {$label} не подтверждён. Подтвердите его или войдите по паролю.");
        }

        return DB::transaction(function () use ($provider, $providerUserId, $email, $social): array {
            $linked = SocialAccount::query()
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->first();

            if ($linked !== null) {
                $user = User::query()->find($linked->user_id);

                if ($user !== null) {
                    return [$user, false];
                }

                $linked->delete(); // связь осталась от удалённого пользователя
            }

            $user = User::query()->where('email', $email)->lockForUpdate()->first();
            $created = false;

            if ($user === null) {
                $user = new User([
                    'name' => Str::limit(trim((string) $social->getName()) ?: Str::before($email, '@'), 255, ''),
                    'email' => $email,
                    'phone' => null,
                    // Пароль неизвестен никому: вход — через аккаунт провайдера или «Забыли пароль?»
                    'password' => Str::random(40),
                ]);
                $user->email_verified_at = now();
                $user->save();
                $created = true;
            } elseif (! $user->hasVerifiedEmail()) {
                $user->forceFill([
                    'password' => Str::random(40),
                    'password_set_at' => null, // прежний пароль недействителен, пользователь задаст свой
                    'remember_token' => Str::random(60),
                ]);
                $user->markEmailAsVerified();
            }

            SocialAccount::query()->create([
                'user_id' => $user->getKey(),
                'provider' => $provider,
                'provider_user_id' => $providerUserId,
                'email' => $email,
            ]);

            return [$user, $created];
        });
    }

    private function providerConfirmedEmail(SocialiteUser $social): bool
    {
        $raw = (array) ($social->user ?? []);
        $flag = $raw['email_verified'] ?? $raw['verified_email'] ?? false;

        return filter_var($flag, FILTER_VALIDATE_BOOL);
    }
}
