<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ImageService;
use App\Services\PackageService;
use App\Services\ProductService;
use App\Services\SellerService;
use App\Services\UserService;
use App\Services\CategoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Регистрация сервисов как singleton
        $this->app->singleton(ImageService::class);
        $this->app->singleton(ProductService::class);
        $this->app->singleton(PackageService::class);
        $this->app->singleton(UserService::class);
        $this->app->singleton(SellerService::class);
        $this->app->singleton(CategoryService::class);
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
