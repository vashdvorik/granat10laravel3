<?php

namespace App\Providers;

use App\Models\Category;  
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

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
    }*/
    

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        
        if (Schema::hasTable('categories')) {
            $categories = \App\Models\Category::whereNull('parent_id')->with('children')->get();
            View::share('categories', $categories);
            View::share('menuRootCategories', $categories); // <-- для push-меню
        }
    }
}
