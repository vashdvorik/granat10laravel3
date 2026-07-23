<?php

namespace App\Rules;

use App\Services\CatalogBannerManager;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CatalogBannerLink implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! app(CatalogBannerManager::class)->isAllowedLink(is_string($value) ? $value : null)) {
            $fail('Укажите внутренний путь, начинающийся с /, или полный адрес http:// либо https://.');
        }
    }
}
