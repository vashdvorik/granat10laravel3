<?php

namespace App\Http\Middleware;

use App\Services\DesignManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\View\FileViewFinder;
use Symfony\Component\HttpFoundation\Response;

class ResolveDesign
{
    public function __construct(private readonly DesignManager $design) {}

    public function handle(Request $request, Closure $next): Response
    {
        $routeName = (string) $request->route()?->getName();

        if ($request->is('account') || $request->is('account/*') || str_starts_with($routeName, 'account.')) {
            return $next($request);
        }

        $theme = $this->design->current();

        View::share('designTheme', $theme);

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
