<?php

namespace App\Http\Middleware;

use App\Models\Settlement;
use App\Services\RegionalContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ResolveRegionalContext
{
    public function __construct(private readonly RegionalContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('regionality.enabled')) {
            return $next($request);
        }

        $location = $request->route('location');

        if (config('regionality.urls_enabled') && is_string($location)) {
            try {
                $settlement = Settlement::query()
                    ->active()
                    ->where('slug', $location)
                    ->whereHas('region', fn ($query) => $query->where('is_active', true))
                    ->with('region')
                    ->first();
            } catch (Throwable) {
                abort(404);
            }

            abort_unless($settlement, 404);

            $this->context->setSettlement($settlement);
            $request->session()->put('regional_settlement_id', $settlement->id);
            $request->route()->forgetParameter('location');

            return $next($request);
        }

        $settlementId = $request->session()->get('regional_settlement_id')
            ?? $request->cookie(config('regionality.cookie_name'));

        if ($settlementId) {
            $settlement = Settlement::query()
                ->active()
                ->with('region')
                ->find($settlementId);

            if ($settlement && $settlement->region?->is_active) {
                $this->context->setSettlement($settlement);
                $request->session()->put('regional_settlement_id', $settlement->id);
            } else {
                $request->session()->forget('regional_settlement_id');
            }
        }

        return $next($request);
    }
}
