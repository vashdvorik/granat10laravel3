<?php

namespace App\Services;

use App\Enums\BalanceTransactionType;
use App\Models\User;
use App\Notifications\AccountNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * «Поблагодарить разработчика»: перевод денег с баланса пользователя на баланс администратора.
 */
class DeveloperTipService
{
    public function recipient(): ?User
    {
        $configured = config('developer_tip.recipient_user_id');

        if (filled($configured)) {
            return User::query()->find((int) $configured);
        }

        return User::query()
            ->where(function ($query): void {
                $query->whereHas('role', fn ($role) => $role->where('name', 'admin'))
                    ->orWhereHas('roles', fn ($role) => $role->where('name', 'admin'));
            })
            ->orderBy('id')
            ->first();
    }

    public function canTip(User $user): bool
    {
        $recipient = $this->recipient();

        return $recipient !== null && ! $recipient->is($user);
    }

    public function minAmount(): int
    {
        return max(1, (int) config('developer_tip.min_amount'));
    }

    public function maxAmount(): int
    {
        return max($this->minAmount(), (int) config('developer_tip.max_amount'));
    }

    /**
     * @return array<int, int>
     */
    public function presets(): array
    {
        return array_values(array_filter(
            array_map('intval', (array) config('developer_tip.presets')),
            fn (int $amount): bool => $amount >= $this->minAmount() && $amount <= $this->maxAmount(),
        ));
    }

    /**
     * Переводит сумму с баланса $from на баланс получателя. Возвращает отправителя с обновлённым балансом.
     */
    public function send(User $from, string $amount): User
    {
        $cents = $this->parseAmount($amount);
        $recipient = $this->recipient();

        if ($recipient === null || $recipient->is($from)) {
            throw ValidationException::withMessages(['amount' => 'Сейчас отправить благодарность нельзя.']);
        }

        return DB::transaction(function () use ($from, $recipient, $cents): User {
            // Блокируем обе строки в порядке id, чтобы параллельные переводы не заблокировали друг друга
            $locked = User::query()
                ->whereIn('id', [$from->getKey(), $recipient->getKey()])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $sender = $locked->get($from->getKey());
            $receiver = $locked->get($recipient->getKey());

            $senderOld = $this->toCents($sender->getRawOriginal('balance') ?? '0');
            $receiverOld = $this->toCents($receiver->getRawOriginal('balance') ?? '0');

            if ($senderOld < $cents) {
                throw ValidationException::withMessages([
                    'amount' => 'Недостаточно средств на балансе. Доступно '.$this->fromCents($senderOld).' ₽.',
                ]);
            }

            $sender->forceFill(['balance' => $this->fromCents($senderOld - $cents)])->save();
            $receiver->forceFill(['balance' => $this->fromCents($receiverOld + $cents)])->save();

            $ledger = app(BalanceLedger::class);
            $ledger->record(
                $sender,
                BalanceTransactionType::TipSent,
                -$cents,
                $senderOld - $cents,
                'Благодарность разработчику',
                meta: ['to_user_id' => $receiver->getKey()],
            );
            $ledger->record(
                $receiver,
                BalanceTransactionType::TipReceived,
                $cents,
                $receiverOld + $cents,
                'Благодарность от '.($sender->name ?: $sender->email),
                meta: ['from_user_id' => $sender->getKey()],
            );

            activity('user_balance')
                ->causedBy($sender)
                ->performedOn($receiver)
                ->withProperties([
                    'source' => 'developer_tip',
                    'amount' => $this->fromCents($cents),
                    'from_user_id' => $sender->getKey(),
                    'from_balance_before' => $this->fromCents($senderOld),
                    'from_balance_after' => $this->fromCents($senderOld - $cents),
                    'to_user_id' => $receiver->getKey(),
                    'to_balance_before' => $this->fromCents($receiverOld),
                    'to_balance_after' => $this->fromCents($receiverOld + $cents),
                ])
                ->log('Благодарность разработчику с баланса пользователя');

            $receiver->notify(new AccountNotification(
                'developer_tip',
                'Вам отправили благодарность',
                ($sender->name ?: $sender->email).' — '.$this->fromCents($cents).' ₽.',
                route('account.user.index'),
                ['from_user_id' => $sender->getKey()],
            ));

            return $sender;
        });
    }

    private function parseAmount(string $amount): int
    {
        $normalized = str_replace(',', '.', trim($amount));

        if (! preg_match('/^\d{1,6}(?:\.\d{1,2})?$/', $normalized)) {
            throw ValidationException::withMessages(['amount' => 'Введите сумму в формате 100 или 100.50.']);
        }

        $cents = $this->toCents($normalized);

        if ($cents < $this->minAmount() * 100 || $cents > $this->maxAmount() * 100) {
            throw ValidationException::withMessages([
                'amount' => 'Сумма благодарности — от '.$this->minAmount().' до '.$this->maxAmount().' ₽.',
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
