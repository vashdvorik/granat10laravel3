<?php

namespace App\Providers;

use App\Models\Category;  
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if (Schema::hasTable('categories')) {
            try {
                // 🆕 NESTED SET: Дерево категорий для меню (с кешированием 24 часа)
                $categories = \App\Models\Category::getCachedRootTree();

                // Фильтруем пустые категории для меню (пуш-меню, футер, поиск), если включено в конфиге
                $menuCategories = config('categories.hide_empty', true)
                    ? Cache::remember('menu_categories', now()->addHours(24), fn () => \App\Models\Category::filterTreeByProducts($categories))
                    : $categories;

                View::share('categories', $menuCategories);
                View::share('menuRootCategories', $menuCategories);
            } catch (\Exception $e) {
                // Игнорируем ошибки при миграциях
                View::share('categories', collect());
                View::share('menuRootCategories', collect());
            }
        } else {
            // Таблица ещё не создана (первая миграция или тестовое окружение)
            View::share('categories', collect());
            View::share('menuRootCategories', collect());
        }

        // Динамические переменные для кабинета
        View::composer([
            'backend.*',
            'backend.cabinet.*',
            'backend.cabinet.store.*',
            'backend.cabinet.user.*',
        ], function ($view) {
            $user = Auth::user();
            $seller = $user ? $user->seller : null;
            $view->with('user', $user)->with('seller', $seller);
        });
    }
}
