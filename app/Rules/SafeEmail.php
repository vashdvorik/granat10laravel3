<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || str_contains($value, "\r") || str_contains($value, "\n")) {
            $fail('Поле :attribute содержит недопустимые символы.');
        }
    }
}
