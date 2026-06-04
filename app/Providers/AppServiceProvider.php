<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

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

        $this->composeMenuCategories();
        $this->composeBackendUser();
    }

    private function composeMenuCategories(): void
    {
        View::composer('*', function ($view) {
            static $menuCategories = null;

            if ($menuCategories === null) {
                $menuCategories = $this->loadMenuCategories();
            }

            $data = $view->getData();

            if (!array_key_exists('categories', $data)) {
                $view->with('categories', $menuCategories);
            }

            if (!array_key_exists('menuRootCategories', $data)) {
                $view->with('menuRootCategories', $menuCategories);
            }
        });
    }

    private function loadMenuCategories()
    {
        try {
            if (!Schema::hasTable('categories')) {
                return collect();
            }

            $categories = Category::getCachedRootTree();

            return config('categories.hide_empty', true)
                ? Cache::remember(
                    'menu_categories',
                    now()->addHours(24),
                    fn () => Category::filterTreeByProducts($categories)
                )
                : $categories;
        } catch (Throwable) {
            return collect();
        }
    }

    private function composeBackendUser(): void
    {
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
