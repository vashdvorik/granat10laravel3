<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\DeveloperTipService;
use Illuminate\Http\Request;

class DeveloperTipController extends Controller
{
    public function __construct(private readonly DeveloperTipService $tips) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'string', 'max:12'],
        ], [
            'amount.required' => 'Укажите сумму.',
        ]);

        $this->tips->send($request->user(), $data['amount']);

        return redirect()
            ->route('account.user.index')
            ->with('success', 'Спасибо! Ваша благодарность отправлена разработчику.');
    }
}
