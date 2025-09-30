<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OnlyMyEmail
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
    $allowed = [
        'kinokaef@gmail.com',
    ];

    if (auth()->check() && in_array(auth()->user()->email, $allowed)) {
        return $next($request);
    }
    abort(403, 'Доступ запрещён');
    }
}
