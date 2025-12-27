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
/*    public function boot(): void
    {
        $categories = Category::whereNull('parent_id')->with('children')->get();

        View::share('categories', $categories);
        View::share('menuRootCategories', $categories); // <-- для push-меню
    }*/
    

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if (Schema::hasTable('categories')) {
            $categories = \App\Models\Category::whereNull('parent_id')->with('children')->get();
            View::share('categories', $categories);
            View::share('menuRootCategories', $categories); // <-- для push-меню
        }

        // Динамические переменные $seller и $user для кабинета
        View::composer([
            'backend.*',
            'backend.cabinet.*',
            'backend.cabinet.store.*',
            'backend.cabinet.user.*',
            // добавьте другие подпапки, если есть
        ], function ($view) {
            $user = Auth::user();
            $seller = $user ? $user->seller : null;
            $view->with('user', $user)->with('seller', $seller);
        });


    }
}
