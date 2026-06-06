<?php

namespace App\Jobs;

use App\Models\Category;
use App\Services\CatalogCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WarmCategoriesCache implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        $cache = app(CatalogCacheService::class);

        // 1. Пересчитываем дерево категорий
        $cache->forgetCategories();

        $tree = Category::getCachedRootTree(); // записывает categories_root_tree

        // 2. Пересчитываем отфильтрованное меню (если включено)
        if (config('categories.hide_empty', true)) {
            $cache->rememberMenuCategories(fn () => Category::filterTreeByProducts($tree));
        }
    }
}
