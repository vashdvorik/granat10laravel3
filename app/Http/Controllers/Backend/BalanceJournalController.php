<?php

namespace App\Http\Controllers\Backend;

use App\Enums\BalanceTransactionType;
use App\Http\Controllers\Controller;
use App\Models\BalanceTopup;
use App\Models\BalanceTransaction;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Единый журнал баланса продавца: все пополнения, списания и возвраты в одном месте. */
class BalanceJournalController extends Controller
{
    private const PERIODS = [
        '30' => 'Последние 30 дней',
        '90' => 'Последние 90 дней',
        '365' => 'Последний год',
        'all' => 'За всё время',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $type = BalanceTransactionType::tryFrom((string) $request->query('type'));
        $period = array_key_exists((string) $request->query('period'), self::PERIODS) ? (string) $request->query('period') : 'all';

        $base = BalanceTransaction::query()
            ->where('user_id', $user->getKey())
            ->when($type, fn (Builder $q) => $q->where('type', $type->value))
            ->when($period !== 'all', fn (Builder $q) => $q->where('created_at', '>=', Carbon::now()->subDays((int) $period)->startOfDay()));

        if ($request->query('export') === 'csv') {
            return $this->exportCsv(clone $base);
        }

        $credited = (int) (clone $base)->where('amount_cents', '>', 0)->sum('amount_cents');
        $spent = abs((int) (clone $base)->where('amount_cents', '<', 0)->sum('amount_cents'));

        return view('backend.cabinet.balance.index', [
            'user' => $user,
            'transactions' => (clone $base)->latest('id')->paginate(20)->withQueryString(),
            'types' => BalanceTransactionType::options(),
            'periods' => self::PERIODS,
            'selectedType' => $type?->value,
            'selectedPeriod' => $period,
            'credited' => $credited,
            'spent' => $spent,
            'balanceCents' => (int) round(((float) $user->getRawOriginal('balance')) * 100),
            // счета, которые клиент ещё может оплатить: деньги пока не пришли
            'pendingTopups' => BalanceTopup::query()
                ->where('user_id', $user->getKey())
                ->where('status', BalanceTopup::STATUS_PENDING)
                ->where('expires_at', '>', now())
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }

    private function exportCsv(Builder $query): StreamedResponse
    {
        $filename = 'balance-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM: Excel корректно откроет кириллицу
            fputcsv($out, ['Дата', 'Операция', 'Описание', 'Сумма, ₽', 'Баланс после, ₽'], ';');

            $query->orderBy('id')->chunk(500, function ($rows) use ($out): void {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->created_at->format('d.m.Y H:i'),
                        $row->type->label(),
                        $row->description,
                        number_format($row->amount_cents / 100, 2, ',', ''),
                        number_format($row->balance_after_cents / 100, 2, ',', ''),
                    ], ';');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
