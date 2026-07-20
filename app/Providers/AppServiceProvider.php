<?php

namespace App\Providers;

use App\Contracts\AiModerationClient;
use App\Contracts\ProductImageModerationClient;
use App\Contracts\TelegramBotClient;
use App\Models\Category;
use App\Services\CatalogCacheService;
use App\Services\CustomerOrderListService;
use App\Services\DesignManager;
use App\Services\ModerationClientResolver;
use App\Services\ModerationSecretStore;
use App\Services\RegionalContext;
use App\Services\RegionalUrlService;
use App\Services\SeoService;
use App\Services\TelegramHttpBotClient;
use Illuminate\Support\Facades\Auth;
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
        $this->app->bind(TelegramBotClient::class, TelegramHttpBotClient::class);
        $this->app->scoped(DesignManager::class);
        $this->app->scoped(RegionalContext::class);
        $this->app->scoped(RegionalUrlService::class);
        $this->app->bind(AiModerationClient::class, function () {
            return app(ModerationClientResolver::class)->text((string) config('moderation.provider'));
        });
        $this->app->bind(ProductImageModerationClient::class, function () {
            return app(ModerationClientResolver::class)->images((string) config('moderation.images.provider'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        app(ModerationSecretStore::class)->applyToConfig();

        $this->composeMenuCategories();
        $this->composeBackendUser();
        $this->composeRegionalContext();
        $this->composeSeo();
        $this->composeCustomerOrders();
    }

    private function composeCustomerOrders(): void
    {
        View::composer('main_layots', function ($view) {
            $view->with(
                'customerOrdersCount',
                app(CustomerOrderListService::class)->count(request())
            );
        });
    }

    private function composeSeo(): void
    {
        View::composer('main_layots', function ($view) {
            $seo = app(SeoService::class);
            $request = request();

            $view
                ->with('canonicalUrl', $seo->canonical($request))
                ->with('robotsMeta', $seo->robots($request))
                ->with('seoTitle', $seo->title($request))
                ->with('seoDescription', $seo->description($request));
        });
    }

    private function composeRegionalContext(): void
    {
        View::composer('main_layots', function ($view) {
            $context = app(RegionalContext::class);

            $view
                ->with('regionalContext', $context)
                ->with('regionalUrl', app(RegionalUrlService::class))
                ->with(
                    'regionalRegions',
                    config('regionality.enabled') ? $context->availableRegions() : collect(),
                );
        });
    }

    private function composeMenuCategories(): void
    {
        View::composer('*', function ($view) {
            static $menuCategories = null;

            if ($menuCategories === null) {
                $menuCategories = $this->loadMenuCategories();
            }

            $data = $view->getData();

            if (! array_key_exists('categories', $data)) {
                $view->with('categories', $menuCategories);
            }

            if (! array_key_exists('menuRootCategories', $data)) {
                $view->with('menuRootCategories', $menuCategories);
            }

            if (! array_key_exists('regionalUrl', $data)) {
                $view->with('regionalUrl', app(RegionalUrlService::class));
            }

            if (! array_key_exists('regionalContext', $data)) {
                $view->with('regionalContext', app(RegionalContext::class));
            }
        });
    }

    private function loadMenuCategories()
    {
        try {
            if (! Schema::hasTable('categories')) {
                return collect();
            }

            $categories = Category::getCachedRootTree();

            return config('categories.hide_empty', true)
                ? app(CatalogCacheService::class)
                    ->rememberMenuCategories(fn () => Category::filterTreeByProducts($categories))
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
