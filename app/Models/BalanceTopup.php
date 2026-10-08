<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BalanceTopup extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected $guarded = ['id'];

    protected $casts = [
        'amount_cents' => 'integer',
        'bank_state' => 'integer',
        'is_test' => 'boolean',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Строка журнала баланса, созданная при зачислении этого платежа (есть только у оплаченных). */
    public function ledgerEntry(): HasOne
    {
        return $this->hasOne(BalanceTransaction::class, 'reference_id')
            ->where('reference_type', $this->getMorphClass());
    }

    /** Платёж ждёт оплаты дольше срока жизни счёта: банк так и не ответил, нужна ручная сверка. */
    public function isOverdue(): bool
    {
        return $this->isPending() && $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED, self::STATUS_EXPIRED => 'gray',
            default => 'warning',
        };
    }

    /** Что означает код состояния, который вернул банк (GetState). */
    public function bankStateLabel(): string
    {
        return match ($this->bank_state) {
            null => 'Банк ещё не отвечал',
            1 => 'Оплачен',
            2 => 'Отменён покупателем',
            3 => 'Отказ банка',
            4 => 'Время счёта истекло',
            default => 'Код '.$this->bank_state,
        };
    }

    /** Идентификатор счёта для банка (nivid/InvoiceId, до 20 символов). */
    public function invoiceId(): string
    {
        return (string) $this->getKey();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function amountFormatted(): string
    {
        return sprintf('%d.%02d', intdiv($this->amount_cents, 100), $this->amount_cents % 100);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Оплачен',
            self::STATUS_FAILED => 'Ошибка оплаты',
            self::STATUS_CANCELLED => 'Отменён',
            self::STATUS_EXPIRED => 'Просрочен',
            default => 'Ожидает оплаты',
        };
    }
}
