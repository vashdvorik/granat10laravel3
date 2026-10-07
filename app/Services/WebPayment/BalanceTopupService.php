<?php

namespace App\Services\WebPayment;

use App\Models\BalanceTopup;
use App\Models\User;
use App\Notifications\AccountNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BalanceTopupService
{
    public function __construct(private readonly WebPaymentClient $client) {}

    public function isAvailable(): bool
    {
        return $this->client->isConfigured();
    }

    public function minAmount(): int
    {
        return max(1, (int) config('services.webpayment.min_amount'));
    }

    public function maxAmount(): int
    {
        return max($this->minAmount(), (int) config('services.webpayment.max_amount'));
    }

    /**
     * Создаёт счёт на пополнение и возвращает параметры формы для PaymentStart.
     *
     * @return array{topup: BalanceTopup, url: string, fields: array<string, string|int>}
     */
    public function start(User $user, string $amount): array
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException('Пополнение баланса временно недоступно.');
        }

        $cents = $this->parseAmount($amount);        $lifetime = max(1, (int) config('services.webpayment.lifetime_minutes'));

        $topup = BalanceTopup::query()->create([
            'user_id' => $user->getKey(),
            'amount_cents' => $cents,            'currency' => (string) config('services.webpayment.currency'),
            'status' => BalanceTopup::STATUS_PENDING,
            'is_test' => (bool) config('services.webpayment.is_test'),
            'expires_at' => now()->addMinutes($lifetime),
        ]);

        $fields = $this->client->paymentFields(
            $topup->invoiceId(),
            $cents,
            'Пополнение баланса, счёт №'.$topup->invoiceId(),
            $lifetime,
            $topup->is_test,
        );

        return ['topup' => $topup, 'url' => $this->client->paymentUrl(), 'fields' => $fields];
    }

    /**
     * Проверяет подпись уведомления банка (ResultURL) и сверяет статус через GetState.
     * Возвращает null, если подпись неверна или счёт неизвестен.
     *
     * @param array<string, mixed> $params
     */
    public function handleResult(array $params): ?BalanceTopup
    {
        $params = array_change_key_case($params, CASE_LOWER);
        $invoiceId = (string) ($params['invoiceid'] ?? '');
        $status = (string) ($params['status'] ?? '');
        $date = (string) ($params['date'] ?? '');
        $signature = strtolower((string) ($params['signature'] ?? ''));

        if (! preg_match('/^\d{1,18}$/', $invoiceId)) {
            return null;
        }

        $expected = match ($status) {
            'paid' => $this->client->paidSignature(
                $invoiceId,
                (string) ($params['paymentsum'] ?? ''),
                (string) ($params['paymentcurrency'] ?? ''),
                $date,
            ),
            'fail' => $this->client->failSignature($invoiceId, $date),
            default => null,
        };

        if ($expected === null || ! hash_equals($expected, $signature)) {
            return null;
        }

        $topup = BalanceTopup::query()->find((int) $invoiceId);

        return $topup === null ? null : $this->settle($topup);
    }

    /**
     * Единственное место, где состояние счёта берётся из банка (GetState) и деньги зачисляются.
     * Идемпотентно: повторные вызовы не зачисляют сумму второй раз.
     */
    public function settle(BalanceTopup $topup): BalanceTopup
    {
        if (! $topup->isPending()) {
            return $topup;
        }

        try {
            $state = $this->client->getState($topup->invoiceId());
        } catch (RuntimeException $e) {
            Log::warning('WebPayment: не удалось получить состояние счёта', [
                'topup_id' => $topup->getKey(),
                'error' => $e->getMessage(),
            ]);

            return $this->expireIfOverdue($topup);
        }

        return DB::transaction(function () use ($topup, $state): BalanceTopup {
            $locked = BalanceTopup::query()->lockForUpdate()->findOrFail($topup->getKey());

            if (! $locked->isPending()) {
                return $locked;
            }

            $locked->bank_state = $state['state'];

            match ($state['state']) {
                1 => $this->applyPaid($locked, $state),
                2 => $locked->status = BalanceTopup::STATUS_CANCELLED,
                3 => $locked->status = BalanceTopup::STATUS_FAILED,
                4 => $locked->status = BalanceTopup::STATUS_EXPIRED,
                default => null,
            };

            $locked->save();

            if (! $locked->isPending() && $locked->status !== BalanceTopup::STATUS_PAID) {
                $this->notifyNotCompleted($locked);
            }

            return $locked->isPending() ? $this->expireIfOverdue($locked) : $locked;
        });
    }

    /** Сверяет ожидающие счета с банком (для планировщика). Возвращает число завершённых счетов. */
    public function reconcilePending(): int
    {
        $count = 0;

        BalanceTopup::query()
            ->where('status', BalanceTopup::STATUS_PENDING)
            ->where('created_at', '<=', now()->subMinute())
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->each(function (BalanceTopup $topup) use (&$count): void {
                if (! $this->settle($topup)->isPending()) {
                    $count++;
                }
            });

        return $count;
    }

    /**
     * @param array{state:int, sum:int, currency:string, istest:int, rrn:?string, lastdgt:?string} $state
     */
    private function applyPaid(BalanceTopup $topup, array $state): void
    {
        $isTestPayment = $state['istest'] === 1;

        if ($state['sum'] !== $topup->amount_cents
            || $state['currency'] !== $topup->currency
            || $isTestPayment !== $topup->is_test
            || ($isTestPayment && ! config('services.webpayment.is_test'))) {
            Log::error('WebPayment: параметры оплаченного счёта не совпадают, зачисление отклонено', [
                'topup_id' => $topup->getKey(),
                'expected_cents' => $topup->amount_cents,
                'bank_sum' => $state['sum'],
                'bank_currency' => $state['currency'],
                'bank_istest' => $state['istest'],
            ]);
            $topup->status = BalanceTopup::STATUS_FAILED;

            return;
        }

        $user = User::query()->lockForUpdate()->findOrFail($topup->user_id);
        $oldCents = $this->toCents((string) ($user->getRawOriginal('balance') ?? '0'));
        $newCents = $oldCents + $topup->amount_cents;

        $user->forceFill(['balance' => $this->fromCents($newCents)])->save();

        $topup->status = BalanceTopup::STATUS_PAID;
        $topup->paid_at = now();
        $topup->rrn = $state['rrn'];
        $topup->card_last_digits = $state['lastdgt'];

        $user->notify(new AccountNotification(
            'balance_topup',
            'Баланс пополнен',
            'Зачислено '.$topup->amountFormatted().' ₽. Доступно: '.$this->fromCents($newCents).' ₽.',
            route('account.user.index'),
            ['topup_id' => $topup->getKey()],
        ));

        activity('user_balance')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'source' => 'webpayment',
                'topup_id' => $topup->getKey(),
                'rrn' => $state['rrn'],
                'old_balance' => $this->fromCents($oldCents),
                'new_balance' => $this->fromCents($newCents),
                'adjustment' => $topup->amountFormatted(),                'is_test' => $topup->is_test,
            ])
            ->log('Пополнение баланса банковской картой');
    }

    private function expireIfOverdue(BalanceTopup $topup): BalanceTopup
    {
        // Запас в 10 минут: оплата могла пройти в последний момент жизни счёта.
        if ($topup->isPending() && $topup->expires_at !== null && $topup->expires_at->copy()->addMinutes(10)->isPast()) {
            $topup->status = BalanceTopup::STATUS_EXPIRED;
            $topup->save();
            $this->notifyNotCompleted($topup);
        }

        return $topup;
    }

    private function notifyNotCompleted(BalanceTopup $topup): void
    {
        $topup->user?->notify(new AccountNotification(
            'balance_topup_failed',
            'Пополнение не выполнено',
            'Платёж на '.$topup->amountFormatted().' ₽ не прошёл ('.mb_strtolower($topup->statusLabel()).'). Деньги на баланс не зачислены. Попробуйте ещё раз.',
            route('account.user.balance.topup'),
            ['topup_id' => $topup->getKey()],
        ));
    }

    private function parseAmount(string $amount): int
    {
        $normalized = str_replace(',', '.', trim($amount));

        if (! preg_match('/^\d{1,6}(?:\.\d{1,2})?$/', $normalized)) {
            throw ValidationException::withMessages(['amount' => 'Введите сумму в формате 100 или 100.50.']);
        }

        $cents = (int) round(((float) $normalized) * 100);

        if ($cents < $this->minAmount() * 100 || $cents > $this->maxAmount() * 100) {
            throw ValidationException::withMessages([
                'amount' => 'Сумма пополнения — от '.$this->minAmount().' до '.$this->maxAmount().' ₽.',
            ]);
        }

        return $cents;
    }

    private function toCents(string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function fromCents(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
