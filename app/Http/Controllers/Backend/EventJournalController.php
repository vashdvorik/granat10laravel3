<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\SellerEventTimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventJournalController extends Controller
{
    public function __invoke(Request $request, SellerEventTimelineService $timeline): View|RedirectResponse
    {
        $seller = $request->user()?->seller;

        if (! $seller) {
            return redirect()
                ->route('account.store.create')
                ->with('error', 'Сначала создайте магазин.');
        }

        return view('backend.cabinet.events.index', [
            'seller' => $seller,
            'events' => $timeline->latestForSeller($seller, 30),
        ]);
    }
}
