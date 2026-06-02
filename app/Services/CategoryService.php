<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Сервис для работы с категориями
 */
class CategoryService
{
    /**
     * Получение дерева категорий с кешированием
     */
    public function getCategoryTree(): Collection
    {
        return Category::getCachedRootTree();
    }

    /**
     * Получение корневых категорий
     */
    public function getRootCategories(): Collection
    {
        return Category::getCachedRootCategories();
    }

    /**
     * Получение категории по ID с кешированием
     */
    public function getCategoryById(int $id): ?Category
    {
        return Cache::remember(
            "category.{$id}",
            now()->addHours(24),
            fn () => Category::with(['ancestors'])->find($id)
        );
    }

    /**
     * Получение категории по slug/alias
     */
    public function getCategoryBySlug(string $slug): ?Category
    {
        return Cache::remember(
            "category.slug.{$slug}",
            now()->addHours(24),
            fn () => Category::where('alias', $slug)->first()
        );
    }

    /**
     * Получение хлебных крошек для категории
     */
    public function getBreadcrumbs(Category $category): Collection
    {
        return Cache::remember(
            "category.{$category->id}.breadcrumbs",
            now()->addHours(24),
            fn () => $category->ancestors()->get()->push($category)
        );
    }

    /**
     * Получение дочерних категорий
     */
    public function getChildren(Category $category): Collection
    {
        return Cache::remember(
            "category.{$category->id}.children",
            now()->addHours(24),
            fn () => $category->children()->where('is_active', true)->get()
        );
    }

    /**
     * Фильтрация дерева категорий - только с товарами
     */
    public function filterTreeByProducts(Collection $tree): Collection
    {
        return Category::filterTreeByProducts($tree);
    }

    /**
     * Очистка кэша категории
     */
    public function clearCache(Category $category): void
    {
        Cache::forget("category.{$category->id}");
        Cache::forget("category.{$category->id}.breadcrumbs");
        Cache::forget("category.{$category->id}.children");
        Cache::forget("category.slug.{$category->slug}");
        Cache::forget('categories.tree');
        Cache::forget('categories.root');
        Cache::forget('menu_categories');
    }

    /**
     * Получение популярных категорий по количеству товаров
     */
    public function getPopularCategories(int $limit = 10): Collection
    {
        return Cache::remember(
            "categories.popular.{$limit}",
            now()->addHours(6),
            fn () => Category::withCount(['products' => function ($query) {
                $query->where('is_active', true);
            }])
                ->having('products_count', '>', 0)
                ->orderByDesc('products_count')
                ->limit($limit)
                ->get()
        );
    }
}
