<?php

namespace App\Services;

use App\Enums\BalanceTransactionType;
use App\Models\BalanceTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Единый журнал баланса. Каждый сервис, который меняет users.balance, вызывает record()
 * в той же транзакции сразу после сохранения нового баланса.
 */
class BalanceLedger
{
    /**
     * @param  int  $amountCents  со знаком: «+» зачисление, «−» списание
     * @param  int  $balanceAfterCents  баланс пользователя ПОСЛЕ операции
     * @param  array<string, mixed>  $meta
     */
    public function record(
        User $user,
        BalanceTransactionType $type,
        int $amountCents,
        int $balanceAfterCents,
        string $description,
        ?Model $reference = null,
        array $meta = [],
    ): BalanceTransaction {
        return BalanceTransaction::query()->create([
            'user_id' => $user->getKey(),
            'type' => $type,
            'amount_cents' => $amountCents,
            'balance_after_cents' => $balanceAfterCents,
            'description' => mb_substr($description, 0, 255),
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
