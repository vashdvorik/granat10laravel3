<?php

use App\Http\Controllers\Backend\BalanceTopupController;
use App\Http\Controllers\CabinetController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RobotsController;
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
// старый адрес главной: постоянный редирект, чтобы у главной страницы был один адрес
Route::permanentRedirect('/home', '/');

// Дерево категорий объединено со страницей «Категории» (управление деревом в одном месте)
if (config('admin.filament_enabled')) {
    $adminPath = trim((string) config('admin.filament_path', 'admin'), '/');
    Route::permanentRedirect('/'.$adminPath.'/category-tree', '/'.$adminPath.'/categories');
}
Route::post('/location', [RegionalSelectionController::class, 'update'])->name('regionality.update');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/sitemap-{page}.xml', SitemapController::class)->whereNumber('page')->name('sitemap.page');
Route::get('/robots.txt', RobotsController::class)->name('robots');

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
Route::get('product/{alias}/order', [OrderController::class, 'create'])->name('orders.create');
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
Route::middleware(['auth', 'cabinet.design'])->prefix('account')->name('account.')->group(function () {
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

// === ВХОД И РЕГИСТРАЦИЯ ЧЕРЕЗ ВНЕШНИЙ АККАУНТ (Google) ===
Route::middleware(['guest', 'throttle:30,1'])
    ->prefix('auth')
    ->name('social.')
    ->whereIn('provider', array_keys(\App\Services\SocialLoginService::PROVIDERS))
    ->group(function () {
        Route::get('{provider}/redirect', [\App\Http\Controllers\Auth\SocialAuthController::class, 'redirect'])->name('redirect');
        Route::get('{provider}/callback', [\App\Http\Controllers\Auth\SocialAuthController::class, 'callback'])->name('callback');
    });

// === ЛИЧНЫЙ КАБИНЕТ (BACKEND) ===
// 'verified': кабинет доступен только после подтверждения почты (письмо со ссылкой отправляется при регистрации)
Route::middleware(['auth', 'verified', 'cabinet.design'])->prefix('account')->name('account.')->group(function () {

    // Главная страница кабинета
    Route::get('/', [CabinetController::class, 'cabinet'])->name('index');

    // Статистика магазина
    Route::get('stats', [CabinetController::class, 'stats'])->name('stats');

    // VIP-размещение: все товары, их VIP, покупка и история
    Route::get('vip', [App\Http\Controllers\Backend\VipController::class, 'index'])->name('vip.index');

    // Журнал баланса (все операции: пополнения, VIP, благодарности, корректировки)
    Route::get('balance', [App\Http\Controllers\Backend\BalanceJournalController::class, 'index'])->name('balance.index');
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
            Route::get('refresh-statuses', [App\Http\Controllers\Backend\ProductController::class, 'refreshStatuses'])
                ->name('refresh-statuses');
            Route::get('{product}', [App\Http\Controllers\Backend\ProductController::class, 'show'])->name('show');
            Route::put('{product}', [App\Http\Controllers\Backend\ProductController::class, 'update'])->name('update');
            Route::delete('{product}', [App\Http\Controllers\Backend\ProductController::class, 'destroy'])->name('destroy');

            // Активация/деактивация товаров
            Route::patch('{product}/activate', [App\Http\Controllers\Backend\ProductController::class, 'activate'])->name('activate');
            Route::patch('{product}/deactivate', [App\Http\Controllers\Backend\ProductController::class, 'deactivate'])->name('deactivate');
            Route::post('{product}/submit-moderation', [App\Http\Controllers\Backend\ProductController::class, 'submitForModeration'])->name('submit-moderation');

            // VIP: покупка/продление за баланс и досрочное отключение с возвратом
            Route::post('{product}/vip', [App\Http\Controllers\Backend\VipController::class, 'purchase'])->name('vip');
            Route::delete('{product}/vip', [App\Http\Controllers\Backend\VipController::class, 'cancel'])->name('vip.cancel');

            // Поднятие товара в списке
            Route::post('{product}/push-up', [App\Http\Controllers\Backend\ProductController::class, 'pushUp'])->name('push-up');

            // Работа с изображениями товаров
            Route::delete('images/{image}', [App\Http\Controllers\Backend\ProductController::class, 'destroyImage'])->name('images.destroy');
            Route::patch('images/{image}/set-main', [App\Http\Controllers\Backend\ProductController::class, 'setMainImage'])->name('images.set-main');
        });

        // Редактирование магазина (ПАРАМЕТРИЗОВАННЫЙ ПУТЬ - В КОНЦЕ!)
        Route::get('{seller}/edit', [App\Http\Controllers\Backend\SellerController::class, 'edit'])->name('edit');
        Route::put('{seller}', [App\Http\Controllers\Backend\SellerController::class, 'update'])->name('update');

        // Telegram-уведомления о новых заказах: отдельный пункт меню магазина
        Route::get('telegram', [App\Http\Controllers\Backend\TelegramConnectionController::class, 'show'])
            ->name('telegram');
        Route::post('telegram/connect', [App\Http\Controllers\Backend\TelegramConnectionController::class, 'connect'])
            ->name('telegram.connect');
        Route::delete('telegram', [App\Http\Controllers\Backend\TelegramConnectionController::class, 'disconnect'])
            ->name('telegram.disconnect');

        // QR-код магазина для печати (ведёт на публичную страницу магазина)
        Route::get('qr', [App\Http\Controllers\Backend\StoreQrController::class, 'index'])->name('qr');
        Route::get('qr/download/{layout}', [App\Http\Controllers\Backend\StoreQrController::class, 'download'])
            ->whereIn('layout', App\Services\ShopQrPoster::LAYOUTS)
            ->name('qr.download');

        Route::get('events', App\Http\Controllers\Backend\EventJournalController::class)
            ->name('events.index');
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

        // Пополнение баланса банковской картой (Web-платёж)
        Route::get('balance/topup', [BalanceTopupController::class, 'create'])
            ->name('balance.topup');
        Route::post('balance/topup', [BalanceTopupController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('balance.topup.store');

        // «Поблагодарить разработчика»: перевод с баланса администратору
        Route::post('balance/tip', [App\Http\Controllers\Backend\DeveloperTipController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('balance.tip');
    });

});

// === WEB-ПЛАТЁЖ: адреса для банка (ResultURL / SuccessURL / FailURL), без CSRF и без параметров в адресе ===
Route::match(['get', 'post'], 'payments/webpayment/result', [BalanceTopupController::class, 'result'])
    ->middleware('throttle:60,1')
    ->name('payments.webpayment.result');
Route::match(['get', 'post'], 'payments/webpayment/success', [BalanceTopupController::class, 'back'])
    ->name('payments.webpayment.success');
Route::match(['get', 'post'], 'payments/webpayment/fail', [BalanceTopupController::class, 'back'])
    ->name('payments.webpayment.fail');

