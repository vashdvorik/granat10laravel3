<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Category;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;

class WarmCategoriesCache implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        // 1. Пересчитываем дерево категорий
        Cache::forget('categories_root_tree');
        Cache::forget('categories_root_list');
        Cache::forget('menu_categories');

        $tree = Category::getCachedRootTree(); // записывает categories_root_tree

        // 2. Пересчитываем отфильтрованное меню (если включено)
        if (config('categories.hide_empty', true)) {
            Cache::remember('menu_categories', now()->addHours(24),
                fn () => Category::filterTreeByProducts($tree)
            );
        }
    }
}
