<?php

namespace App\Providers;

use App\Contracts\AiModerationClient;
use App\Contracts\ProductImageModerationClient;
use App\Contracts\TelegramBotClient;
use App\Models\Category;
use App\Services\CatalogCacheService;
use App\Services\CustomerOrderListService;
use App\Services\DesignManager;
use App\Services\ModerationSecretStore;
use App\Services\OpenRouterImageModerationClient;
use App\Services\OpenRouterModerationClient;
use App\Services\RegionalContext;
use App\Services\RegionalUrlService;
use App\Services\SeoPageContext;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use App\Services\TelegramHttpBotClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
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
        $this->app->scoped(\App\Services\SchemaInspector::class);
        $this->app->scoped(RegionalUrlService::class);
        $this->app->scoped(SeoPageContext::class);
        $this->app->bind(AiModerationClient::class, OpenRouterModerationClient::class);
        $this->app->bind(ProductImageModerationClient::class, OpenRouterImageModerationClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        app(ModerationSecretStore::class)->applyToConfig();

        // @assetv('css/style.css') печатает адрес файла с датой изменения: после выкладки браузер берёт новую версию
        Blade::directive('assetv', fn (string $expression): string => "<?php echo \\App\\Support\\AssetVersion::url({$expression}); ?>");

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
        // Макет: canonical, robots, описание, Open Graph и микроразметка — одинаково для всех тем оформления
        View::composer('main_layots', function ($view) {
            $request = request();

            try {
                app(SeoPageContext::class)->useViewData($view->getData());
                $seo = app(SeoService::class);

                $view
                    ->with('canonicalUrl', $seo->canonical($request))
                    ->with('robotsMeta', $seo->robots($request))
                    ->with('seoTitle', $seo->title($request))
                    ->with('seoDescription', $seo->description($request))
                    ->with('seoOgType', $seo->ogType($request))
                    ->with('seoOgImage', $seo->ogImage($request))
                    ->with('structuredData', app(StructuredDataService::class)->forRequest($request))
                    ->with('seoCities', app(SeoPageContext::class)->citiesWithProducts(12));
            } catch (Throwable $exception) {
                // SEO-обогащение не должно ронять страницу: отдаём безопасные значения и пишем причину в журнал
                report($exception);

                $view
                    ->with('canonicalUrl', $request->url())
                    ->with('robotsMeta', 'index, follow')
                    ->with('seoTitle', '')
                    ->with('seoDescription', '')
                    ->with('seoOgType', 'website')
                    ->with('seoOgImage', null)
                    ->with('structuredData', [])
                    ->with('seoCities', collect());
            }
        });

        // H1 страниц-списков: «Мыши в Тирасполе», «Каталог товаров в Тирасполе» (тот же текст для всех тем)
        View::composer(['home', 'catalog.index', 'catalog.show', 'sellers', 'salepage'], function ($view) {
            try {
                app(SeoPageContext::class)->useViewData($view->getData());
                $view->with('seoHeading', app(SeoService::class)->heading(request()));
            } catch (Throwable $exception) {
                report($exception);
                $view->with('seoHeading', null);
            }
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
            if (! app(\App\Services\SchemaInspector::class)->hasTable('categories')) {
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
