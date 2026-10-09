<?php

namespace App\Models;

use App\Casts\SafeEncrypted;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected $guarded = ['*'];

    protected $casts = [
        'status' => OrderStatus::class,
        // Личные данные покупателя шифруются; нечитаемое значение даёт «Данные недоступны», а не ошибку 500
        'customer_name' => SafeEncrypted::class,
        'customer_phone' => SafeEncrypted::class,
        'delivery_address' => SafeEncrypted::class,
        'customer_comment' => SafeEncrypted::class,
        'original_unit_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'consent_accepted_at' => 'datetime',
        'status_changed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'anonymized_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function scopeForSeller(Builder $query, Seller $seller): Builder
    {
        return $query->where('seller_id', $seller->id);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->latest('created_at');
    }

    /** Это поле не удалось расшифровать (например, заказ создан до смены ключа шифрования). */
    public function isUnreadable(string $field): bool
    {
        return $this->getAttribute($field) === SafeEncrypted::UNREADABLE;
    }

    public function hasUnreadableData(): bool
    {
        foreach (['customer_name', 'customer_phone', 'delivery_address', 'customer_comment'] as $field) {
            if ($this->isUnreadable($field)) {
                return true;
            }
        }

        return false;
    }

    public function isDelivery(): bool    {
        return $this->fulfillment_method === 'delivery';
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }
}
