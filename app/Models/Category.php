<?php

namespace App\Models;

use App\Services\CatalogCacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'number' => 'integer',
    ];

    /* ДОБАВИЛ ПРИ ПЕРЕХОДЕ НА ПАКЕТ NESTED SET */
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
     * в самой категории или в её потомках.
     *
     * Раньше на каждую категорию уходило три запроса (границы, потомки, наличие товаров): для дерева из
     * 1600 категорий это около 5000 запросов за одну страницу. Теперь два запроса на всё дерево:
     * какие категории содержат видимые товары и где они стоят в дереве (границы вложенных множеств).
     */
    public static function filterTreeByProducts($categories)
    {
        $ids = \App\Models\Product::visible()
            ->forRegionalContext()
            ->distinct()
            ->pluck('category_id');

        $lefts = $ids->isEmpty()
            ? []
            : self::query()->whereIn('id', $ids)->pluck('_lft')->map(fn ($left): int => (int) $left)->sort()->values()->all();

        return static::pruneWithoutProducts($categories, $lefts);
    }

    /** @param  array<int, int>  $lefts  отсортированные левые границы категорий, в которых есть видимые товары */
    private static function pruneWithoutProducts($categories, array $lefts)
    {
        return $categories->filter(function ($category) use ($lefts) {
            // Фильтруем детей рекурсивно
            if ($category->children->isNotEmpty()) {
                $category->setRelation('children', static::pruneWithoutProducts($category->children, $lefts));
            }

            return static::hasLeftWithin($lefts, (int) $category->_lft, (int) $category->_rgt);
        })->values();
    }

    /** Есть ли среди отсортированных чисел хотя бы одно в отрезке [from, to] (двоичный поиск). */
    private static function hasLeftWithin(array $lefts, int $from, int $to): bool
    {
        $low = 0;
        $high = count($lefts) - 1;

        while ($low <= $high) {
            $middle = intdiv($low + $high, 2);

            if ($lefts[$middle] < $from) {
                $low = $middle + 1;
            } else {
                $high = $middle - 1;
            }
        }

        return $low < count($lefts) && $lefts[$low] <= $to;
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
        app(CatalogCacheService::class)->forgetCategoryRelated(! static::$suppressCacheWarming);
    }
}
