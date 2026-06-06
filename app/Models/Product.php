<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\CatalogCacheService;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'price', 'lowprice', 'is_vip', 'is_active', 'category_id', 'seller_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('products')
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Товар создан: "' . $this->name . '"',
                'updated' => 'Товар обновлён: "' . $this->name . '"',
                'deleted' => 'Товар удалён: "' . $this->name . '"',
                default   => $eventName,
            });
    }

    protected $fillable = [
        'name',
        'price',
        'lowprice',
        'desc',
        'features1',
        'features2',
        'features3',
        'features4',
        'alias',
        'is_vip',
        'is_active',
        'category_id',
        'seller_id',      
        'sale_started_at', 
        'push_at',        
    ];
    protected $guarded = [
    'id',

    ];
    protected $casts = [
        'push_at' => 'datetime',
        'sale_started_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    public function seller()
    {
        return $this->belongsTo(\App\Models\Seller::class);
    }
    
    public function images()
    {
        return $this->hasMany(\App\Models\Image::class);
    }   

    /**
     * Scope: только товары от продавцов с активными пакетами
     */
    public function scopeFromActiveSellerPackage($query)
    {
        return $query->whereHas('seller.sellerPackages', function ($q) {
            $q->where('is_active', true)
                ->where(function($dateQuery) {
                    $dateQuery->whereNull('end_date')
                        ->orWhere('end_date', '>=', now()->toDateString()); // DATE vs DATE, не DATETIME
                });
        });
    }

    /**
     * Scope: публичные товары 
     * (продавец активировал И у продавца есть активный пакет)
     */
    public function scopeVisible($query)
    {
        return $query->where('is_active', true)  // продавец сам активировал
            ->fromActiveSellerPackage();         // и пакет активен
    }

    // ==========================================
    // РАБОТА С КЕШЕМ
    // ==========================================

    /**
     * Очистить все кеши, связанные с товарами
     */
    public static function clearProductCaches($category = null, $oldCategoryId = null)
    {
        app(CatalogCacheService::class)->forgetProductCatalog($category, $oldCategoryId);
    }

    /**
     * Получить закешированное количество товаров в категории (включая подкатегории)
     */
    public static function getCategoryProductsCount($categoryId)
    {
        return app(CatalogCacheService::class)->rememberCategoryProductsCount((int) $categoryId, function () use ($categoryId) {
            $category = \App\Models\Category::find($categoryId);
            if (!$category) {
                return 0;
            }
            
            $categoryIds = \App\Models\Category::whereDescendantOrSelf($categoryId)->pluck('id');
            return self::visible()
                ->whereIn('category_id', $categoryIds)
                ->count();
        });
    }

    /**
     * Получить закешированный диапазон цен товаров
     */
    public static function getCachedPriceRange()
    {
        return app(CatalogCacheService::class)->rememberProductPriceRange(function () {
            return [
                'min' => self::min('price') ?? 0,
                'max' => self::max('price') ?? 0,
            ];
        });
    }

}
