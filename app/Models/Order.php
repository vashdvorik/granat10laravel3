<?php

namespace App\Models;

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
        'customer_name' => 'encrypted',
        'customer_phone' => 'encrypted',
        'delivery_address' => 'encrypted',
        'customer_comment' => 'encrypted',
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

    public function isDelivery(): bool
    {
        return $this->fulfillment_method === 'delivery';
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }
}
