<?php

namespace App\Http\Controllers;

use App\Models\Settlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegionalSelectionController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        abort_unless(config('regionality.enabled'), 404);

        $validated = $request->validate([
            'settlement_id' => [
                'nullable',
                'integer',
                Rule::exists('settlements', 'id')->where('is_active', true),
            ],
            'return_to' => ['nullable', 'url'],
        ]);

        $settlementId = $validated['settlement_id'] ?? null;
        $cookieName = config('regionality.cookie_name');

        if ($settlementId === null) {
            $request->session()->forget('regional_settlement_id');

            return redirect()->to($this->redirectTarget($request, null))
                ->withCookie(cookie()->forget($cookieName));
        }

        $settlement = Settlement::query()
            ->active()
            ->whereHas('region', fn ($query) => $query->where('is_active', true))
            ->findOrFail($settlementId);

        $request->session()->put('regional_settlement_id', $settlement->id);

        return redirect()->to($this->redirectTarget($request, $settlement->slug))->withCookie(cookie(
            $cookieName,
            (string) $settlement->id,
            config('regionality.cookie_minutes'),
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax',
        ));
    }

    private function redirectTarget(Request $request, ?string $location): string
    {
        $returnTo = $request->input('return_to', url()->previous());

        if (! is_string($returnTo) || ! Str::startsWith($returnTo, url('/'))) {
            return route('home');
        }

        $path = trim((string) parse_url($returnTo, PHP_URL_PATH), '/');
        $segments = $path === '' ? [] : explode('/', $path);
        $firstSegment = $segments[0] ?? null;

        if ($firstSegment && Settlement::query()->where('slug', $firstSegment)->exists()) {
            array_shift($segments);
        }

        if ($location) {
            array_unshift($segments, $location);
        }

        $target = url(implode('/', $segments));
        $query = parse_url($returnTo, PHP_URL_QUERY);

        return $query ? $target.'?'.$query : $target;
    }
}
