<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\CatalogCacheService;
use Kalnoy\Nestedset\NodeTrait;

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

    /*ДОБАВИЛ ПРИ ПЕРЕХОДЕ НА ПАКЕТ NESTED SET */
     /**
     * Получить breadcrumbs (хлебные крошки) для категории
     */
    public function getBreadcrumbsAttribute()
    {
        return $this->getAncestors()->push($this);
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
        return app(CatalogCacheService::class)->rememberRootTree(function () {
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
        return app(CatalogCacheService::class)->rememberRootCategories(function () {
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
        app(CatalogCacheService::class)->forgetCategoryRelated(!static::$suppressCacheWarming);
    }
}
