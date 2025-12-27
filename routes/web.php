<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController; 
use App\Http\Controllers\CatalogController; 
use App\Http\Controllers\PageController; 
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\CabinetController;

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

/*Route::get('/', [HomeController::class, 'home']);*/ 
Route::get('/', [HomeController::class, 'home'])->name('home');
Route::get('/home', [HomeController::class, 'home'])->name('home');
/*
Route::get('catalog', [CatalogController::class, 'catalog']);
*/
Route::get('/catalog', [CatalogController::class, 'catalog'])->name('catalog.index');
Route::get('/catalog/{alias}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/sale', [CatalogController::class, 'sale'])->name('catalog.sale');

// роутs для карточки товара
Route::get('product/{alias}', [ProductController::class, 'show'])->name('product.show');

// роутs для SellerController
Route::get('/shops', [SellerController::class, 'shops'])->name('shops');
Route::get('/shops/{alias}', [SellerController::class, 'seller'])->name('seller');
// роутs для обычных страниц
Route::get('gobuyer', [PageController::class, 'gobuyer'])->name('gobuyer');
Route::get('help', [PageController::class, 'help'])->name('help');

// не пропускать страницы, которые не админы и не прописаны в app/Http/Middleware/OnlyMyEmail.php
Route::group(['prefix' => 'admin', 'middleware' => ['web', 'auth', 'only.my.email']], function () {
    Voyager::routes();
});
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

// Роут создания магазина для продавца
Route::middleware('auth')->prefix('cabinet')->group(function () {
    Route::get('/create-shop', [CabinetController::class, 'createShop'])->name('cabinet.create_shop');
    Route::post('/create-shop', [CabinetController::class, 'storeShop'])->name('cabinet.store_shop');
    // 
});

Route::resource('backend/sellers', \App\Http\Controllers\Backend\SellerController::class);

