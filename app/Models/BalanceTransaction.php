<?php

namespace App\Models;

use App\Enums\BalanceTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Строка единого журнала баланса. Создаётся только через BalanceLedger. */
class BalanceTransaction extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'type' => BalanceTransactionType::class,
        'amount_cents' => 'integer',
        'balance_after_cents' => 'integer',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCredit(): bool
    {
        return $this->amount_cents > 0;
    }

    /** «+500.00» / «−120.00» */
    public function amountFormatted(): string
    {
        return ($this->amount_cents >= 0 ? '+' : '−').self::money(abs($this->amount_cents));
    }

    public function balanceAfterFormatted(): string
    {
        return self::money($this->balance_after_cents);
    }

    public static function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', ' ');
    }
}
