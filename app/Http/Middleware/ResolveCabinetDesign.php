<?php

namespace App\Http\Middleware;

use App\Services\CabinetDesignManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\View\FileViewFinder;
use Symfony\Component\HttpFoundation\Response;

class ResolveCabinetDesign
{
    public function __construct(private readonly CabinetDesignManager $design) {}

    public function handle(Request $request, Closure $next): Response
    {
        $theme = $this->design->current();

        View::share('cabinetDesignTheme', $theme);

        $finder = View::getFinder();

        if (! $finder instanceof FileViewFinder) {
            return $next($request);
        }

        $originalPaths = $finder->getPaths();
        $themePath = $this->design->viewPath($theme);

        if (is_dir($themePath)) {
            $finder->prependLocation($themePath);
            $finder->flush();
        }

        try {
            return $next($request);
        } finally {
            $finder->setPaths($originalPaths)->flush();
        }
    }
}
