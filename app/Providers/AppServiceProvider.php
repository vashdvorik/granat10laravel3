<?php

namespace App\Providers;

use App\Models\Category;  
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
                    
                View::share('categories', $categories);
                View::share('menuRootCategories', $categories);
            } catch (\Exception $e) {
                // Игнорируем ошибки при миграциях
                View::share('categories', collect());
                View::share('menuRootCategories', collect());
            }
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
