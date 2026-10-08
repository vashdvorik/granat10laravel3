<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Защита публичных форм регистрации и восстановления пароля от ботов и перебора:
 * лимиты по IP, скрытое поле-ловушка, подписанная метка времени формы и (если заданы ключи) Cloudflare Turnstile.
 */
class ProtectAuthForms
{
    public const HONEYPOT_FIELD = 'contact_url';

    public const TOKEN_FIELD = 'form_token';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        $form = match (true) {
            $request->is('register') => 'register',
            $request->is('forgot-password') => 'forgot_password',
            default => null,
        };

        if ($form === null) {
            return $next($request);
        }

        if ($seconds = $this->tooManyAttempts($form, $request)) {
            return $this->reject($request, "Слишком много попыток. Повторите через {$seconds} сек.", 429);
        }

        if ($request->filled(self::HONEYPOT_FIELD)) {
            Log::warning('Auth form: honeypot filled', ['form' => $form, 'ip' => $request->ip()]);

            return $this->reject($request, 'Не удалось отправить форму. Обновите страницу и попробуйте снова.');
        }

        if (($problem = $this->checkFormToken($request)) !== null) {
            return $this->reject($request, $problem);
        }

        if (! $this->passesTurnstile($request)) {
            return $this->reject($request, 'Не пройдена проверка «я не робот». Попробуйте ещё раз.');
        }

        return $next($request);
    }

    /** Подписанная метка времени, выданная при показе формы (см. partials/form-protection). */
    public static function issueToken(): string
    {
        return Crypt::encryptString((string) time());
    }

    private function tooManyAttempts(string $form, Request $request): ?int
    {
        $limits = [
            "auth-form:{$form}:minute:{$request->ip()}" => [(int) config("auth_protection.{$form}.per_minute"), 60],
            "auth-form:{$form}:day:{$request->ip()}" => [(int) config("auth_protection.{$form}.per_day"), 86400],
        ];

        foreach ($limits as $key => [$max, $decay]) {
            if ($max > 0 && RateLimiter::tooManyAttempts($key, $max)) {
                return max(1, RateLimiter::availableIn($key));
            }
        }

        foreach ($limits as $key => [$max, $decay]) {
            RateLimiter::hit($key, $decay);
        }

        return null;
    }

    private function checkFormToken(Request $request): ?string
    {
        $token = (string) $request->input(self::TOKEN_FIELD, '');

        try {
            $issuedAt = (int) Crypt::decryptString($token);
        } catch (DecryptException) {
            return 'Форма устарела или повреждена. Обновите страницу и попробуйте снова.';
        }

        $age = time() - $issuedAt;

        if ($age < (int) config('auth_protection.min_fill_seconds')) {
            return 'Форма отправлена слишком быстро. Попробуйте ещё раз.';
        }

        if ($age > (int) config('auth_protection.max_form_age_minutes') * 60) {
            return 'Форма устарела. Обновите страницу и попробуйте снова.';
        }

        return null;
    }

    private function passesTurnstile(Request $request): bool
    {
        $secret = config('auth_protection.turnstile.secret');

        if (blank($secret) || blank(config('auth_protection.turnstile.site_key'))) {
            return true; // капча не настроена
        }

        $response = (string) $request->input('cf-turnstile-response', '');

        if ($response === '') {
            return false;
        }

        try {
            $result = Http::asForm()
                ->timeout((int) config('auth_protection.turnstile.timeout', 5))
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secret,
                    'response' => $response,
                    'remoteip' => $request->ip(),
                ]);

            return (bool) $result->json('success', false);
        } catch (Throwable $e) {
            // Сервис проверки недоступен: не блокируем регистрацию из-за чужого сбоя (остальные защиты работают)
            Log::warning('Turnstile unavailable', ['error' => $e->getMessage()]);

            return true;
        }
    }

    private function reject(Request $request, string $message, int $status = 302): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status === 302 ? 422 : $status);
        }

        return back()
            ->withInput($request->except(['password', 'password_confirmation', self::TOKEN_FIELD]))
            ->withErrors(['email' => $message]);
    }
}
