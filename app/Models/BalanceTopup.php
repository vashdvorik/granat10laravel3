<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceTopup extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected $guarded = ['id'];

    protected $casts = [
        'amount_cents' => 'integer',        'is_test' => 'boolean',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
