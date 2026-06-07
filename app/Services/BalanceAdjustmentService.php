<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BalanceAdjustmentService
{
    public function adjust(User $actor, User $target, int|float|string $amount, string $reason): User
    {
        Gate::forUser($actor)->authorize('adjustBalance', $target);

        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Укажите причину корректировки баланса.',
            ]);
        }

        $adjustmentInCents = $this->toCents($amount, 'amount');

        if ($adjustmentInCents === 0) {
            throw ValidationException::withMessages([
                'amount' => 'Сумма корректировки не может быть равна нулю.',
            ]);
        }

        return DB::transaction(function () use ($actor, $target, $adjustmentInCents, $reason): User {
            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($target->getKey());

            $oldBalanceInCents = $this->toCents($lockedUser->getRawOriginal('balance') ?? '0', 'balance');
            $newBalanceInCents = $oldBalanceInCents + $adjustmentInCents;

            if ($newBalanceInCents < 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Итоговый баланс не может быть отрицательным.',
                ]);
            }

            $oldBalance = $this->fromCents($oldBalanceInCents);
            $newBalance = $this->fromCents($newBalanceInCents);
            $adjustment = $this->fromCents($adjustmentInCents);

            $lockedUser->forceFill(['balance' => $newBalance])->save();

            activity('user_balance')
                ->causedBy($actor)
                ->performedOn($lockedUser)
                ->withProperties([
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'adjustment' => $adjustment,
                    'reason' => $reason,
                ])
                ->log('Корректировка баланса пользователя');

            return $lockedUser->refresh();
        });
    }

    private function toCents(int|float|string $amount, string $field): int
    {
        $normalized = str_replace(',', '.', trim((string) $amount));

        if (! preg_match('/^-?\d{1,8}(?:\.\d{1,2})?$/', $normalized)) {
            throw ValidationException::withMessages([
                $field => 'Сумма должна соответствовать формату decimal(10,2).',
            ]);
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '-');
        [$units, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $cents = ((int) $units * 100) + (int) str_pad($fraction, 2, '0');

        return $negative ? -$cents : $cents;
    }

    private function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return sprintf('%s%d.%02d', $sign, intdiv($absolute, 100), $absolute % 100);
    }
}
