<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/** Запрещает адреса одноразовых почтовых сервисов (список — config/auth_protection.php). */
class NotDisposableEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! str_contains($value, '@')) {
            return;
        }

        $domain = Str::lower(trim(Str::after($value, '@')));
        $blocked = array_map('strtolower', (array) config('auth_protection.disposable_domains', []));

        foreach ($blocked as $blockedDomain) {
            if ($domain === $blockedDomain || str_ends_with($domain, '.'.$blockedDomain)) {
                $fail('Временные почтовые адреса не принимаются. Укажите ваш постоянный адрес.');

                return;
            }
        }
    }
}
