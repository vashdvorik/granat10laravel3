<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Покупка VIP-размещения товара за баланс. */
class VipPurchase extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = ['id'];

    protected $casts = [
        'days' => 'integer',
        'price_cents' => 'integer',
        'refunded_cents' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Операции журнала баланса по этой покупке: списание при покупке и возврат при отключении. */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class, 'reference_id')
            ->where('reference_type', $this->getMorphClass())
            ->orderBy('id');
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /** Период идёт прямо сейчас. */
    public function isRunning(): bool
    {
        return ! $this->isCancelled() && $this->starts_at->isPast() && $this->ends_at->isFuture();
    }

    public function priceFormatted(): string
    {
        return number_format($this->price_cents / 100, 2, '.', ' ');
    }

    /** running | scheduled | finished | cancelled */
    public function statusKey(): string
    {
        if ($this->isCancelled()) {
            return 'cancelled';
        }

        if ($this->starts_at->isFuture()) {
            return 'scheduled';
        }

        return $this->ends_at->isFuture() ? 'running' : 'finished';
    }

    public function statusLabel(): string
    {
        return match ($this->statusKey()) {
            'cancelled' => 'Отключён досрочно',
            'scheduled' => 'Ожидает начала',
            'running' => 'Действует',
            default => 'Завершён',
        };
    }

    public function statusColor(): string
    {
        return match ($this->statusKey()) {
            'running' => 'success',
            'scheduled' => 'info',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }
}
