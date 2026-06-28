<?php

use App\Http\Controllers\CabinetController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RegionalSelectionController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
/*
ПУБЛИЧНЕЫ РОУТЫ САЙТА
*/

Route::get('/health/live', [HealthController::class, 'live'])->name('health.live');
Route::get('/health/ready', [HealthController::class, 'ready'])->name('health.ready');
Route::post('/webhooks/telegram', App\Http\Controllers\TelegramWebhookController::class)
    ->name('webhooks.telegram');

Route::get('/', [HomeController::class, 'home'])->name('home');
Route::get('/home', [HomeController::class, 'home']);
Route::post('/location', [RegionalSelectionController::class, 'update'])->name('regionality.update');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/catalog', [CatalogController::class, 'catalog'])->name('catalog.index');
Route::get('/catalog/load-more', [CatalogController::class, 'loadMoreCatalog'])->name('catalog.loadMore');
Route::get('/catalog/{alias}/load-more', [CatalogController::class, 'loadMoreCategory'])->name('catalog.loadMoreCategory');
Route::get('/catalog/{alias}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/sale', [CatalogController::class, 'sale'])->name('catalog.sale');
Route::get('/sale/load-more', [CatalogController::class, 'loadMoreSale'])->name('catalog.loadMoreSale');

// Поиск товаров
Route::get('/search', [CatalogController::class, 'search'])->name('search');
Route::get('/search/load-more', [CatalogController::class, 'loadMoreSearch'])->name('search.loadMore');

// Избранные товары
Route::get('/favorites', [App\Http\Controllers\FavoritesController::class, 'index'])->name('favorites');
Route::get('/favorites/products', [App\Http\Controllers\FavoritesController::class, 'getProducts'])->name('favorites.products');
Route::get('/favorites/load-more', [App\Http\Controllers\FavoritesController::class, 'loadMoreFavorites'])->name('favorites.loadMore');

// роутs для карточки товара
Route::get('product/{alias}', [ProductController::class, 'show'])->name('product.show');
Route::post('product/{product}/order', [OrderController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('orders.store');
Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::delete('orders', [OrderController::class, 'clear'])->name('orders.clear');
Route::get('orders/{publicId}', [OrderController::class, 'show'])->name('orders.show');
Route::delete('orders/{publicId}', [OrderController::class, 'destroy'])->name('orders.destroy');

// роутs для SellerController
Route::get('/shops', [SellerController::class, 'shops'])->name('shops');
Route::get('/shops/load-more', [SellerController::class, 'loadMoreShops'])->name('shops.loadMore');
Route::get('/shops/{alias}', [SellerController::class, 'seller'])->name('seller');
Route::get('/shops/{alias}/load-more', [SellerController::class, 'loadMoreSellerProducts'])->name('seller.loadMore');

Route::get('/seller-help', [PageController::class, 'sellerHelp'])->name('seller.help');
Route::get('/gobuyer', [PageController::class, 'gobuyer'])->name('gobuyer');
Route::get('/help', [PageController::class, 'help'])->name('help');

if (config('regionality.urls_enabled')) {
    $reservedLocations = implode('|', array_map(
        static fn (string $slug): string => preg_quote($slug, '/'),
        config('regionality.reserved_slugs', [])
    ));

    Route::prefix('{location}')
        ->where(['location' => '^(?!('.$reservedLocations.')$)[a-z0-9-]+$'])
        ->name('regional.')
        ->group(function () {
            Route::get('/', [HomeController::class, 'home'])->name('home');
            Route::get('/catalog', [CatalogController::class, 'catalog'])->name('catalog.index');
            Route::get('/catalog/load-more', [CatalogController::class, 'loadMoreCatalog'])->name('catalog.loadMore');
            Route::get('/catalog/{alias}/load-more', [CatalogController::class, 'loadMoreCategory'])->name('catalog.loadMoreCategory');
            Route::get('/catalog/{alias}', [CatalogController::class, 'show'])->name('catalog.show');
            Route::get('/sale', [CatalogController::class, 'sale'])->name('catalog.sale');
            Route::get('/sale/load-more', [CatalogController::class, 'loadMoreSale'])->name('catalog.loadMoreSale');
            Route::get('/search', [CatalogController::class, 'search'])->name('search');
            Route::get('/search/load-more', [CatalogController::class, 'loadMoreSearch'])->name('search.loadMore');
            Route::get('/favorites', [App\Http\Controllers\FavoritesController::class, 'index'])->name('favorites');
            Route::get('/favorites/products', [App\Http\Controllers\FavoritesController::class, 'getProducts'])->name('favorites.products');
            Route::get('/favorites/load-more', [App\Http\Controllers\FavoritesController::class, 'loadMoreFavorites'])->name('favorites.loadMore');
            Route::get('/product/{alias}', [ProductController::class, 'show'])->name('product.show');
            Route::get('/shops', [SellerController::class, 'shops'])->name('shops');
            Route::get('/shops/load-more', [SellerController::class, 'loadMoreShops'])->name('shops.loadMore');
            Route::get('/shops/{alias}', [SellerController::class, 'seller'])->name('seller');
            Route::get('/shops/{alias}/load-more', [SellerController::class, 'loadMoreSellerProducts'])->name('seller.loadMore');
        });
}
// роутs для обычных страниц

/*
// роут для личного кабинета
Route::get('/account', [CabinetController::class, 'cabinet'])
    ->middleware('auth')
    ->name('cabinet');


// Группировка роутов личного кабинета с префиксом /account
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::prefix('store')->name('store.')->group(function () {
        Route::get('/', [CabinetController::class, 'storeIndex'])->name('index');
        Route::get('products', [CabinetController::class, 'storeProducts'])->name('products');
        Route::get('about', [CabinetController::class, 'storeAbout'])->name('about');
    });

    // пользователь
    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/', [CabinetController::class, 'userIndex'])->name('index');
        Route::get('change-password', [CabinetController::class, 'showChangePasswordForm'])->name('change_password');
        Route::post('change-password', [CabinetController::class, 'updatePassword'])->name('update_password');
    });
});

// Роут создания и удаления магазина для продавца
Route::middleware('auth')->prefix('cabinet')->group(function () {
    Route::get('/create-shop', [CabinetController::class, 'createShop'])->name('cabinet.create_shop');
    Route::post('/create-shop', [CabinetController::class, 'storeShop'])->name('cabinet.store_shop');
    Route::get('/delete-shop', [CabinetController::class, 'deleteShop'])->name('cabinet.delete_shop');
    Route::delete('/delete-shop', [CabinetController::class, 'destroyShop'])->name('cabinet.destroy_shop');
});
*/

// === ЛИЧНЫЙ КАБИНЕТ (BACKEND) ===
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {

    // Главная страница кабинета
    Route::get('/', [CabinetController::class, 'cabinet'])->name('index');

    // Статистика магазина
    Route::get('stats', [CabinetController::class, 'stats'])->name('stats');
    Route::get('notifications/{notification}', [App\Http\Controllers\Backend\NotificationController::class, 'read'])
        ->name('notifications.read');
    Route::post('notifications/read-all', [App\Http\Controllers\Backend\NotificationController::class, 'readAll'])
        ->name('notifications.read-all');

    // Получение дочерних категорий (AJAX)
    Route::get('categories/children', [App\Http\Controllers\Backend\ProductController::class, 'getChildCategories'])
        ->name('categories.children');

    // === РАЗДЕЛ МАГАЗИНА ===
    Route::prefix('store')->name('store.')->group(function () {
        // Общая информация магазина
        Route::get('/', [App\Http\Controllers\Backend\SellerController::class, 'index'])->name('index');

        // Создание магазина (КОНКРЕТНЫЙ ПУТЬ - ПЕРВЫМ!)
        Route::get('create', [CabinetController::class, 'createShop'])->name('create');
        Route::post('create', [CabinetController::class, 'storeShop'])->name('store');

        // Удаление магазина (КОНКРЕТНЫЙ ПУТЬ - ПЕРВЫМ!)
        Route::get('delete', [CabinetController::class, 'deleteShop'])->name('delete');
        Route::delete('delete', [CabinetController::class, 'destroyShop'])->name('destroy');

        // === ТОВАРЫ МАГАЗИНА (КОНКРЕТНЫЙ ПУТЬ - ПЕРВЫМ!) ===
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [App\Http\Controllers\Backend\ProductController::class, 'index'])->name('index');
            Route::get('create', [App\Http\Controllers\Backend\ProductController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Backend\ProductController::class, 'store'])->name('store');
            Route::get('{product}/edit', [App\Http\Controllers\Backend\ProductController::class, 'edit'])->name('edit');
            Route::get('{product}', [App\Http\Controllers\Backend\ProductController::class, 'show'])->name('show');
            Route::put('{product}', [App\Http\Controllers\Backend\ProductController::class, 'update'])->name('update');
            Route::delete('{product}', [App\Http\Controllers\Backend\ProductController::class, 'destroy'])->name('destroy');

            // Активация/деактивация товаров
            Route::patch('{product}/activate', [App\Http\Controllers\Backend\ProductController::class, 'activate'])->name('activate');
            Route::patch('{product}/deactivate', [App\Http\Controllers\Backend\ProductController::class, 'deactivate'])->name('deactivate');
            Route::post('{product}/submit-moderation', [App\Http\Controllers\Backend\ProductController::class, 'submitForModeration'])->name('submit-moderation');

            // Поднятие товара в списке
            Route::post('{product}/push-up', [App\Http\Controllers\Backend\ProductController::class, 'pushUp'])->name('push-up');

            // Работа с изображениями товаров
            Route::delete('images/{image}', [App\Http\Controllers\Backend\ProductController::class, 'destroyImage'])->name('images.destroy');
            Route::patch('images/{image}/set-main', [App\Http\Controllers\Backend\ProductController::class, 'setMainImage'])->name('images.set-main');
        });

        // Редактирование магазина (ПАРАМЕТРИЗОВАННЫЙ ПУТЬ - В КОНЦЕ!)
        Route::get('{seller}/edit', [App\Http\Controllers\Backend\SellerController::class, 'edit'])->name('edit');
        Route::put('{seller}', [App\Http\Controllers\Backend\SellerController::class, 'update'])->name('update');

        Route::post('toggle-auto-renew', [CabinetController::class, 'toggleAutoRenew'])
            ->name('toggle-auto-renew');
        Route::post('telegram/connect', [App\Http\Controllers\Backend\TelegramConnectionController::class, 'connect'])
            ->name('telegram.connect');
        Route::delete('telegram', [App\Http\Controllers\Backend\TelegramConnectionController::class, 'disconnect'])
            ->name('telegram.disconnect');

        Route::get('orders', [App\Http\Controllers\Backend\OrderController::class, 'index'])
            ->name('orders.index');
        Route::get('orders/{order}', [App\Http\Controllers\Backend\OrderController::class, 'show'])
            ->name('orders.show');
        Route::patch('orders/{order}/status', [App\Http\Controllers\Backend\OrderController::class, 'updateStatus'])
            ->name('orders.status');
    });

    // === РАЗДЕЛ ПОЛЬЗОВАТЕЛЯ ===
    Route::prefix('user')->name('user.')->group(function () {
        // Общая информация
        Route::get('/', [CabinetController::class, 'userIndex'])->name('index');

        // Редактирование профиля
        Route::get('edit', [App\Http\Controllers\Backend\UserProfileController::class, 'edit'])->name('edit');
        Route::put('/', [App\Http\Controllers\Backend\UserProfileController::class, 'update'])->name('update');

        // Смена пароля
        Route::get('change-password', [CabinetController::class, 'showChangePasswordForm'])->name('change_password');
        Route::post('change-password', [CabinetController::class, 'updatePassword'])->name('update_password');
    });

    // === РАЗДЕЛ ТАРИФОВ ===
    Route::prefix('packages')->name('packages.')->group(function () {
        Route::get('/', [App\Http\Controllers\Backend\PackageController::class, 'index'])->name('index');
        Route::post('{package}/purchase', [App\Http\Controllers\Backend\PackageController::class, 'purchase'])->name('purchase');
    });
});

// ТЕСТОВЫЙ РОУТ - УДАЛИТЬ ПОСЛЕ ПРОВЕРКИ
if (app()->environment('local')) {
    Route::get('/test-package-system', function (\App\Services\SellerPackageStatusService $sellerPackageStatusService) {
        $total = \App\Models\Product::count();
        $visible = \App\Models\Product::visible()->count();
        $hidden = $total - $visible;

        $sellers = \App\Models\Seller::with(['sellerPackages', 'user'])->get();

        $output = '<h2>Проверка системы пакетов</h2>';
        $output .= "<p><strong>Всего товаров:</strong> $total</p>";
        $output .= "<p><strong>Видимых (с активным пакетом):</strong> $visible</p>";
        $output .= "<p><strong>Скрытых (без пакета):</strong> $hidden</p>";
        $output .= '<hr>';

        $output .= '<h3>Продавцы:</h3>';
        foreach ($sellers as $seller) {
            $activePackage = $seller->activePackage();
            $status = $sellerPackageStatusService->getStatus($seller);

            $output .= "<div style='border:1px solid #ccc; padding:10px; margin:10px 0;'>";
            $output .= "<p><strong>{$seller->name}</strong> (ID: {$seller->id})</p>";
            $balance = $seller->user?->balance ?? 0;
            $output .= "<p>Баланс: {$balance} руб | Автопродление: ".($seller->auto_renew ? 'Вкл' : 'Выкл').'</p>';
            $output .= '<p>Товаров активных: '.$seller->products()->where('is_active', true)->count().'</p>';

            if ($activePackage) {
                $output .= "<p style='color:green;'>✅ Активный пакет: {$activePackage->package->name}</p>";
                $output .= '<p>Срок: '.($activePackage->end_date ? $activePackage->end_date->format('d.m.Y') : 'Бессрочный').'</p>';
            } else {
                $output .= "<p style='color:red;'>❌ Нет активного пакета</p>";
            }

            $output .= "<p><em>{$status['message']}</em></p>";
            $output .= '</div>';
        }

        return $output;
    });
}
