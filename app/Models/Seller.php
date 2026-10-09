<?php

namespace App\Models;

use App\Services\CatalogCacheService;
use App\Services\RegionalContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Seller extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone', 'town', 'sellerplace', 'address', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('sellers')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Магазин создан: "'.$this->name.'"',
                'updated' => 'Магазин обновлён: "'.$this->name.'"',
                'deleted' => 'Магазин удалён: "'.$this->name.'"',
                default => $eventName,
            });
    }

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'town',
        'settlement_id',
        'sellerplace',
        'address',
        'description',
        'alias',
        'link',
        'link_map',
        'telegram',
        'instagram',
        'viber',
        'photos',
        'photo',
        'can_order',
        'delivery_enabled',
        'delivery_terms',
        'telegram_notifications_enabled',
        'telegram_chat_id',
        'telegram_verified_at',

    ];

    protected $guarded = [
        'id',
        'product_count', // Автоматически считается
    ];

    protected $casts = [
        'can_order' => 'boolean',
        'delivery_enabled' => 'boolean',
        'telegram_notifications_enabled' => 'boolean',
        'telegram_verified_at' => 'datetime',
        'hidden_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by_user_id');
    }

    /** Магазин скрыт администратором: на сайте его нет, а причину видит только владелец в кабинете. */
    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    /** Магазины, которые можно показывать покупателям. */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereNull($query->qualifyColumn('hidden_at'));
    }

    public function scopeHiddenByAdmin(Builder $query): Builder
    {
        return $query->whereNotNull($query->qualifyColumn('hidden_at'));
    }

    public function scopeForRegionalContext(Builder $query, ?RegionalContext $context = null): Builder
    {
        $context ??= app(RegionalContext::class);
        $settlement = $context->settlement();

        return $query->when(
            $settlement,
            fn (Builder $regionalQuery) => $regionalQuery->where(
                $regionalQuery->qualifyColumn('settlement_id'),
                $settlement->id
            )
        );
    }

    // ==========================================
    // РАБОТА С КЕШЕМ
    // ==========================================

    /**
     * Получить закешированный список продавцов
     */
    public static function getCachedAll()
    {
        return app(CatalogCacheService::class)->rememberSellers(function () {
            return self::forRegionalContext()->publiclyVisible()->withCount(['products' => function ($query) {
                $query->where('is_active', true);
            }])
                ->orderBy('products_count', 'desc')
                ->get();
        });
    }

    /**
     * Получить продавца по alias из кеша
     */
    public static function getCachedByAlias($alias)
    {
        if (! $alias) {
            return null;
        }

        return app(CatalogCacheService::class)->rememberSellerByAlias($alias, function () use ($alias) {
            return self::forRegionalContext()->publiclyVisible()->withCount(['products' => function ($query) {
                $query->where('is_active', true);
            }])
                ->where('alias', $alias)
                ->first();
        });
    }

    /**
     * Очистить кеш продавцов
     */
    public static function clearSellersCache($sellerInstance = null)
    {
        app(CatalogCacheService::class)->forgetSellerRelated($sellerInstance);
    }
}
