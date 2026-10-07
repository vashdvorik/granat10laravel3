<?php

namespace App\Console\Commands;

use App\Services\WebPayment\BalanceTopupService;
use Illuminate\Console\Command;

class ReconcileBalanceTopups extends Command
{
    protected $signature = 'balance:reconcile-topups';

    protected $description = 'Сверяет ожидающие пополнения баланса с банком (GetState) и зачисляет оплаченные';

    public function handle(BalanceTopupService $topups): int
    {
        if (! $topups->isAvailable()) {
            return self::SUCCESS;
        }

        $this->info('Завершено счетов: '.$topups->reconcilePending());

        return self::SUCCESS;
    }
}
