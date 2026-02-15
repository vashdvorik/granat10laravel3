<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    use HasFactory, NodeTrait;

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
     * Получить закешированное дерево корневых категорий
     */
    public static function getCachedRootTree()
    {
        return Cache::remember('categories_root_tree', now()->addHours(24), function () {
            return self::whereIsRoot()
                ->where('is_active', true)
                ->defaultOrder()
                ->get()
                ->toTree();
        });
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
    public static function clearCategoriesCache()
    {
        Cache::forget('categories_root_tree');
        Cache::forget('categories_root_list');
        
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
