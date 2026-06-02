<?php

declare(strict_types=1);

namespace App\Models;

use App\Jobs\WarmCategoriesCache;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Kalnoy\Nestedset\NodeTrait;

/**
 * @property int $id
 * @property string $name
 * @property string $alias
 * @property int|null $number
 * @property string|null $desc
 * @property string|null $description
 * @property string|null $slug
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $icon
 * @property string|null $icon_color
 * @property string|null $bg_color
 * @property int|null $parent_id
 * @property int $_lft
 * @property int $_rgt
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Category> $children
 * @property-read \App\Models\Category|null $parent
 * @property-read array $breadcrumbs
 */
class Category extends Model
{
    use HasFactory, NodeTrait;

    /**
     * При true — clearCategoriesCache() не диспатчит WarmCategoriesCache.
     * Используется в сидерах, чтобы не пересчитывать кеш на каждое сохранение.
     */
    public static bool $suppressCacheWarming = false;

    protected $fillable = [
        'name',
        'alias',
        'number',
        'desc',
        'icon',
        'icon_color',
        'bg_color',
        'parent_id',
    ];

    /* ДОБАВИЛ ПРИ ПЕРЕХОДЕ НА ПАКЕТ NESTED SET */
    /**
     * Получить breadcrumbs (хлебные крошки) для категории
     */
    protected function breadcrumbs(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getAncestors()->push($this),
        );
    }

    /**
     * Получить товары категории
     */
    public function products()
    {
        return $this->hasMany(\App\Models\Product::class);
    }

    /**
     * Получить все товары включая товары подкатегорий
     */
    public function allProducts()
    {
        $categoryIds = Category::whereDescendantOrSelf($this->id)->pluck('id');

        return \App\Models\Product::whereIn('category_id', $categoryIds);
    }

    // ==========================================
    // РАБОТА С КЕШЕМ
    // ==========================================

    /**
     * Получить закешированное дерево корневых категорий (только с товарами)
     */
    public static function getCachedRootTree()
    {
        return Cache::remember('categories_root_tree', now()->addHours(24), function () {
            return self::where('is_active', true)
                ->defaultOrder()
                ->get()
                ->toTree();
        });
    }

    /**
     * Рекурсивно фильтрует дерево категорий — оставляет только те, у которых есть видимые товары
     */
    public static function filterTreeByProducts($categories)
    {
        return $categories->filter(function ($category) {
            // Фильтруем детей рекурсивно
            if ($category->children->isNotEmpty()) {
                $filteredChildren = static::filterTreeByProducts($category->children);
                $category->setRelation('children', $filteredChildren);
            }

            // Проверяем наличие видимых товаров в категории или её потомках
            $categoryIds = self::descendantsAndSelf($category->id)->pluck('id');

            return \App\Models\Product::visible()
                ->whereIn('category_id', $categoryIds)
                ->exists();
        })->values();
    }

    /**
     * Получить закешированные корневые категории (для форм)
     */
    public static function getCachedRootCategories()
    {
        return Cache::remember('categories_root_list', now()->addHours(24), function () {
            return self::whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('number')
                ->get();
        });
    }

    /**
     * Очистить кеш категорий
     */
    public static function clearCategoriesCache(): void
    {
        Cache::forget('categories_root_tree');
        Cache::forget('categories_root_list');
        Cache::forget('menu_categories');

        if (static::$suppressCacheWarming) {
            return; // Сидер вызовет вручную после всех операций
        }

        // Пересчитываем кеш после отправки ответа — первый посетитель не ждёт
        if (app()->runningInConsole()) {
            dispatch(new WarmCategoriesCache);
        } else {
            dispatch(new WarmCategoriesCache)->afterResponse();
        }
    }

    /**
     * Автоматическая очистка кеша при изменениях
     */
    protected static function booted()
    {
        static::saved(function ($category) {
            self::clearCategoriesCache();
        });

        static::deleted(function ($category) {
            self::clearCategoriesCache();
        });
    }
}
