<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;

class RegionalUrlService
{
    public function __construct(private readonly RegionalContext $context)
    {
    }

    public function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $settlement = $this->context->settlement();

        if (! config('regionality.urls_enabled') || ! $settlement) {
            return route($name, $parameters, $absolute);
        }

        $regionalName = str_starts_with($name, 'regional.') ? $name : 'regional.'.$name;

        if (! Route::has($regionalName)) {
            return route($name, $parameters, $absolute);
        }

        $parameters = is_array($parameters) ? $parameters : [$parameters];

        return route(
            $regionalName,
            ['location' => $settlement->slug, ...$parameters],
            $absolute
        );
    }
}
