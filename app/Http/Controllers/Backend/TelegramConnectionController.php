<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\TelegramConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TelegramConnectionController extends Controller
{
    public function connect(Request $request, TelegramConnectionService $connections): RedirectResponse
    {
        $seller = $request->user()->seller;
        abort_if($seller === null, 404);
        $this->authorize('manageSettings', $seller);

        if (blank(config('services.telegram.bot_username'))) {
            return back()->with('error', 'Telegram-бот пока не настроен администратором.');
        }

        return redirect()->away($connections->createLink($seller));
    }

    public function disconnect(Request $request, TelegramConnectionService $connections): RedirectResponse
    {
        $seller = $request->user()->seller;
        abort_if($seller === null, 404);
        $this->authorize('manageSettings', $seller);
        $connections->disconnect($seller);

        return back()->with('success', 'Telegram отключён.');
    }
}
