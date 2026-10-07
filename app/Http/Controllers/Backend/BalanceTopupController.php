<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\BalanceTopup;
use App\Services\WebPayment\BalanceTopupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class BalanceTopupController extends Controller
{
    public function __construct(private readonly BalanceTopupService $topups) {}

    /** Форма пополнения и история пополнений. */
    public function create()
    {
        $user = Auth::user();

        return view('backend.cabinet.user.topup', [
            'user' => $user,
            'available' => $this->topups->isAvailable(),
            'minAmount' => $this->topups->minAmount(),
            'maxAmount' => $this->topups->maxAmount(),
            'history' => BalanceTopup::query()
                ->where('user_id', $user->getKey())
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    /** Создаёт счёт и отправляет клиента на платёжную страницу банка. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'string', 'max:12'],        ], [
            'amount.required' => 'Укажите сумму пополнения.',
        ]);

        try {
            $payment = $this->topups->start($request->user(), $data['amount']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return view('backend.cabinet.user.topup_redirect', [
            'url' => $payment['url'],
            'fields' => $payment['fields'],
        ]);
    }

    /** ResultURL: серверное оповещение банка (п. 4 документации). */
    public function result(Request $request)
    {
        $topup = $this->topups->handleResult($request->all());

        if ($topup === null) {
            return response('invalid request', 400)->header('Content-Type', 'text/plain');
        }

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /** SuccessURL / FailURL: возврат клиента с сайта банка. Итог определяется только по GetState. */
    public function back(Request $request)
    {
        $invoiceId = (string) ($request->input('invoiceid', $request->input('InvoiceId', '')));
        $topup = preg_match('/^\d{1,18}$/', $invoiceId) ? BalanceTopup::query()->find((int) $invoiceId) : null;

        if ($topup === null) {
            return redirect()->route('account.user.index');
        }

        $topup = $this->topups->settle($topup);

        return redirect()
            ->route('account.user.balance.topup')
            ->with(match ($topup->status) {
                BalanceTopup::STATUS_PAID => ['success' => 'Баланс пополнен на '.$topup->amountFormatted().' ₽.'],
                BalanceTopup::STATUS_PENDING => ['success' => 'Платёж обрабатывается банком. Баланс обновится после подтверждения.'],
                default => ['error' => 'Оплата не выполнена. Деньги с карты не списаны либо будут возвращены банком.'],
            });
    }
}
