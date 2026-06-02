<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $user_id
 * @property int $seller_id
 * @property int $category_id
 * @property string $name
 * @property float $price
 * @property float|null $lowprice
 * @property string|null $desc
 * @property string|null $description
 * @property string|null $features1
 * @property string|null $features2
 * @property string|null $features3
 * @property string|null $features4
 * @property string $alias
 * @property bool $is_vip
 * @property bool $is_active
 * @property int|null $main_image_id
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $sale_started_at
 * @property string|null $push_at
 * @property int $views_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category $category
 * @property-read \App\Models\Seller $seller
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Image> $images
 */
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
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Товар создан: "'.$this->name.'"',
                'updated' => 'Товар обновлён: "'.$this->name.'"',
                'deleted' => 'Товар удалён: "'.$this->name.'"',
                default => $eventName,
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
        'user_id',
        'sale_started_at',
        'push_at',
    ];

    protected $casts = [
        'push_at' => 'datetime',
        'sale_started_at' => 'datetime',
    ];

    /**
     * Категория товара
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Продавец товара
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /**
     * Изображения товара
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    /**
     * Scope: только товары от продавцов с активными пакетами
     */
    public function scopeFromActiveSellerPackage($query)
    {
        return $query->whereHas('seller.sellerPackages', function ($q) {
            $q->where('is_active', true)
                ->where(function ($dateQuery) {
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
        // 1. Очистить кеш диапазона цен
        Cache::forget('product_price_range');

        // 2. Очистить меню (видимость категорий зависит от наличия товаров)
        Cache::forget('menu_categories');

        // 3. Очистить счетчики для текущей категории и всех родителей
        if ($category) {
            self::clearCategoryCountCache($category);
        }

        // 4. Если категория изменилась, очистить и старую категорию
        if ($oldCategoryId && $oldCategoryId != $category?->id) {
            $oldCategory = \App\Models\Category::find($oldCategoryId);
            if ($oldCategory) {
                self::clearCategoryCountCache($oldCategory);
            }
        }
    }

    /**
     * Очистить кеш счетчиков для категории и всех родителей
     */
    private static function clearCategoryCountCache($category)
    {
        if (! $category) {
            return;
        }

        // Очистить кеш для текущей категории
        Cache::forget('category_products_count:'.$category->id);

        // Очистить кеш для всех родителей (товар влияет на их count)
        $ancestors = $category->ancestors;
        foreach ($ancestors as $ancestor) {
            Cache::forget('category_products_count:'.$ancestor->id);
        }
    }

    /**
     * Получить закешированное количество товаров в категории (включая подкатегории)
     */
    public static function getCategoryProductsCount($categoryId)
    {
        return Cache::remember('category_products_count:'.$categoryId, now()->addMinutes(10), function () use ($categoryId) {
            $category = \App\Models\Category::find($categoryId);
            if (! $category) {
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
        return Cache::remember('product_price_range', now()->addHours(1), function () {
            return [
                'min' => self::min('price') ?? 0,
                'max' => self::max('price') ?? 0,
            ];
        });
    }

    /**
     * Автоматическая очистка кеша при изменениях
     */
    protected static function booted()
    {
        // Статический массив для хранения старых категорий (не сохраняется в БД)
        static $oldCategories = [];

        // При создании товара
        static::created(function ($product) {
            self::clearProductCaches($product->category);
            // Передаем экземпляр продавца для точечной очистки
            \App\Models\Seller::clearSellersCache($product->seller);
        });

        // При обновлении товара - сохраняем старую категорию
        static::updating(function ($product) use (&$oldCategories) {
            $oldCategories[$product->id] = $product->getOriginal('category_id');
        });

        static::updated(function ($product) use (&$oldCategories) {
            $oldCategoryId = $oldCategories[$product->id] ?? null;

            // clearProductCaches уже очищает product_price_range, дублирование убрано
            self::clearProductCaches($product->category, $oldCategoryId);

            // Передаем экземпляр продавца для точечной очистки
            \App\Models\Seller::clearSellersCache($product->seller);

            // Очищаем из массива после использования
            unset($oldCategories[$product->id]);
        });

        // При удалении товара
        static::deleted(function ($product) {
            self::clearProductCaches($product->category);
            // Передаем экземпляр продавца для точечной очистки
            \App\Models\Seller::clearSellersCache($product->seller);
        });
    }
}
