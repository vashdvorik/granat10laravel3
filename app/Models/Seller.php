<?php

namespace App\Models;

use App\Services\CatalogCacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'sellerplace',
        'address',
        'description',
        'alias',
        'region_id',
        'link',
        'link_map',
        'telegram',
        'instagram',
        'viber',
        'photos',
        'photo',
        'auto_renew',

    ];

    protected $guarded = [
        'id',
        'product_count', // Автоматически считается
    ];

    protected $casts = [
        'can_order' => 'boolean',
        'auto_renew' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Связь с пакетами через таблицу seller_packages
     */
    public function sellerPackages()
    {
        return $this->hasMany(SellerPackage::class);
    }

    public function activeSellerPackage(): HasOne
    {
        return $this->hasOne(SellerPackage::class)
            ->where('is_active', true)
            ->where(function ($query) {
                $query
                    ->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->latestOfMany();
    }

    /**
     * Получить активный пакет продавца
     */
    public function activePackage()
    {
        return $this->sellerPackages()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('end_date')                         // Бессрочный пакет
                    ->orWhere('end_date', '>=', now()->toDateString()); // Или срок еще не истек (сравниваем DATE с DATE)
            })
            ->with('package')
            ->first();
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
            return self::withCount(['products' => function ($query) {
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
            return self::withCount(['products' => function ($query) {
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
