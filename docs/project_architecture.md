# Документация проекта Laravel - Маркетплейс "Гранат"

## Обзор проекта

**Проект**: Маркетплейс для продавцов и покупателей  
**Версия Laravel**: 10.10  
**PHP**: ^8.1  
**Основные библиотеки**:
- Laravel Fortify (аутентификация)
- Laravel Sanctum (API токены)
- Spatie Activity Log (логирование действий)
- Kalnoy Nested Set (древовидные категории)
- TCG Voyager (админ-панель)
- Intervention Image (обработка изображений)

## Основная концепция

Проект реализует маркетплейс, где:
- **Продавцы** могут создавать магазины, добавлять товары и управлять ими
- **Покупатели** могут просматривать каталог, искать товары, добавлять в избранное
- **Система тарифов** контролирует количество активных товаров у продавца
- **Категории** организованы в древовидную структуру (Nested Set)
- **Кеширование** используется для оптимизации производительности

---

## Структура базы данных

### Основные таблицы

#### `users`
- Пользователи системы (покупатели и продавцы)
- Поля: `id`, `name`, `email`, `password`, `phone`, `balance`, `timestamps`
- Связь: `hasOne(Seller)` - один пользователь может иметь один магазин

#### `sellers`
- Магазины продавцов
- Поля: `id`, `user_id`, `name`, `alias`, `phone`, `town`, `address`, `description`, `auto_renew`, `timestamps`
- Связи:
  - `belongsTo(User)` - принадлежит пользователю
  - `hasMany(Product)` - имеет много товаров
  - `hasMany(SellerPackage)` - имеет историю тарифов

#### `products`
- Товары продавцов
- Поля: `id`, `seller_id`, `category_id`, `name`, `alias`, `price`, `lowprice`, `desc`, `features1-4`, `is_active`, `is_vip`, `sale_started_at`, `push_at`, `timestamps`
- Связи:
  - `belongsTo(Seller)` - принадлежит продавцу
  - `belongsTo(Category)` - принадлежит категории
  - `hasMany(Image)` - имеет много изображений
- Индексы: `['is_active', 'is_vip', 'push_at']` для оптимизации выборки

#### `categories`
- Иерархические категории товаров (Nested Set)
- Поля: `id`, `parent_id`, `name`, `alias`, `desc`, `icon`, `icon_color`, `bg_color`, `_lft`, `_rgt`, `is_active`, `timestamps`
- Использует пакет `kalnoy/nestedset` для эффективной работы с деревом
- Индексы: `['_lft', '_rgt']` для быстрого поиска в дереве

#### `images`
- Изображения товаров
- Поля: `id`, `product_id`, `img`, `is_main`, `timestamps`
- Связь: `belongsTo(Product)`

#### `packages`
- Тарифные планы для продавцов
- Поля: `id`, `name`, `price`, `active_products_limit`, `vip_products_limit`, `duration_days`, `timestamps`

#### `seller_packages`
- История активаций тарифов продавцами
- Поля: `id`, `seller_id`, `package_id`, `is_active`, `start_date`, `end_date`, `timestamps`
- Связи:
  - `belongsTo(Seller)`
  - `belongsTo(Package)`

#### `views`
- Счетчик просмотров товаров
- Поля: `product_id`, `views`

---

## Архитектура приложения

### Модели (app/Models/)

#### Product
**Основные методы и Scopes**:
- `scopeVisible($query)` - только товары с активными тарифами продавцов
- `scopeFromActiveSellerPackage($query)` - фильтр по активным пакетам
- `clearProductCaches()` - очистка кеша при изменении товара
- `getCategoryProductsCount($categoryId)` - кешированный подсчет товаров

**Связи**:
- `category()` - категория товара
- `seller()` - продавец товара
- `images()` - изображения товара

**Fillable**: `name`, `price`, `lowprice`, `desc`, `features1-4`, `alias`, `is_vip`, `is_active`, `category_id`, `seller_id`, `sale_started_at`, `push_at`

**Логирование**: Использует `Spatie\Activitylog` для записи изменений

#### Category
**Основные методы**:
- `getCachedRootTree()` - кешированное дерево категорий (24 часа)
- `filterTreeByProducts($categories)` - фильтрация категорий с товарами
- `allProducts()` - все товары категории и подкатегорий
- `getBreadcrumbsAttribute()` - хлебные крошки для навигации

**Особенности**:
- Использует `NodeTrait` от `kalnoy/nestedset`
- Поддержка древовидной структуры с `_lft` и `_rgt`
- Кеширование на уровне модели

#### Seller
**Основные методы**:
- `activePackage()` - получить активный тариф
- `canActivateProduct(?int $excludeProductId)` - проверка лимитов
- `canActivateVipProduct(?int $excludeProductId)` - проверка VIP лимитов
- `getActiveProductsCount()` - количество активных товаров
- `getVipProductsCount()` - количество VIP товаров
- `getPackageStatus()` - статус тарифа (active/expired/none)

**Связи**:
- `user()` - владелец магазина
- `products()` - товары магазина
- `sellerPackages()` - история тарифов

**Fillable**: `user_id`, `name`, `phone`, `town`, `sellerplace`, `address`, `description`, `alias`, `auto_renew`

**Логирование**: Использует `Spatie\Activitylog`

#### User
**Связи**:
- `seller()` - магазин пользователя

**Fillable**: `name`, `email`, `password`, `phone`, `balance`

---

### Контроллеры (app/Http/Controllers/)

#### Публичная часть (Frontend)

##### HomeController
- `home()` - главная страница сайта

##### CatalogController
- `catalog()` - главная страница каталога
- `show($alias)` - страница категории
- `sale()` - товары со скидками
- `search()` - поиск товаров
- `loadMoreCatalog()` - AJAX подгрузка товаров каталога
- `loadMoreCategory($alias)` - AJAX подгрузка товаров категории
- `loadMoreSale()` - AJAX подгрузка товаров со скидкой
- `loadMoreSearch()` - AJAX подгрузка результатов поиска

**Особенности**:
- Использует `Session` для хранения фильтров
- Кеширование диапазона цен
- Сортировка: VIP → обычные → по `push_at`
- Пагинация: 12 товаров на страницу

##### ProductController
- `show($alias)` - детальная страница товара
- Инкрементирует счетчик просмотров в таблице `views`

##### SellerController
- `shops()` - список всех магазинов
- `seller($alias)` - страница магазина
- `loadMoreShops()` - AJAX подгрузка магазинов
- `loadMoreSellerProducts($alias)` - AJAX подгрузка товаров магазина

##### FavoritesController
- `index()` - страница избранного
- `getProducts()` - получение товаров из избранного (AJAX)
- `loadMoreFavorites()` - AJAX подгрузка избранного

**Механизм работы**:
- ID избранных товаров хранятся в localStorage браузера
- JavaScript отправляет массив ID на сервер
- Сервер возвращает данные товаров

##### PageController
- `gobuyer()` - страница "Как покупать"
- `help()` - страница помощи

---

#### Личный кабинет (Backend)

##### CabinetController
**Основные методы**:
- `cabinet()` - главная страница кабинета
- `stats()` - статистика магазина (товары, просмотры, тарифы)
- `createShop()` - форма создания магазина
- `storeShop(Request)` - сохранение нового магазина
- `deleteShop()` - форма удаления магазина
- `destroyShop()` - удаление магазина
- `toggleAutoRenew()` - включение/выключение автопродления тарифа
- `userIndex()` - страница профиля пользователя
- `showChangePasswordForm()` - форма смены пароля
- `updatePassword(Request)` - обновление пароля

**Валидация**:
- Валидация выполняется вручную через `Validator::make()` в контроллере
- Нет использования Form Requests (несоответствие стандартам)

##### Backend\ProductController
**CRUD товаров**:
- `index(Request)` - список товаров продавца (с фильтрами)
- `create()` - форма создания товара
- `store(Request)` - сохранение товара
- `show(Product)` - просмотр товара
- `edit(Product)` - форма редактирования
- `update(Request, Product)` - обновление товара
- `destroy(Product)` - удаление товара

**Дополнительные методы**:
- `activate(Product)` - активация товара (проверка лимитов)
- `deactivate(Product)` - деактивация товара
- `pushUp(Product)` - поднятие товара в списке (обновление `push_at`)
- `destroyImage(ProductImage)` - удаление изображения
- `setMainImage(ProductImage)` - установка главного изображения
- `getChildCategories(Request)` - AJAX получение дочерних категорий

**Бизнес-логика**:
- Проверка лимитов тарифа перед активацией
- Обработка изображений через Intervention Image
- Генерация уникального alias из названия товара
- Очистка кешей при изменении товаров

##### Backend\SellerController
- `index()` - информация о магазине
- `edit(Seller)` - форма редактирования магазина
- `update(Request, Seller)` - обновление данных магазина

##### Backend\PackageController
- `index()` - список доступных тарифов
- `purchase(Package)` - покупка тарифного плана

**Логика покупки**:
1. Проверка баланса пользователя
2. Списание средств с баланса
3. Создание записи в `seller_packages`
4. Установка даты окончания тарифа
5. Очистка кешей товаров

##### Backend\UserProfileController
- `edit()` - форма редактирования профиля
- `update(Request)` - обновление данных пользователя

---

#### Админ-панель

##### Admin\ActivityLogController
- `index()` - просмотр логов активности (использует Spatie Activity Log)

##### Admin\CategoryTreeController
- `index()` - визуализация дерева категорий

**Защита**: Middleware `only.my.email` - доступ только для определенных email

---

### Маршрутизация (routes/)

#### routes/web.php

**Публичные маршруты**:
```php
Route::get('/', [HomeController::class, 'home'])->name('home');
Route::get('/catalog', [CatalogController::class, 'catalog'])->name('catalog.index');
Route::get('/catalog/{alias}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/product/{alias}', [ProductController::class, 'show'])->name('product.show');
Route::get('/shops', [SellerController::class, 'shops'])->name('shops');
Route::get('/shops/{alias}', [SellerController::class, 'seller'])->name('seller');
Route::get('/search', [CatalogController::class, 'search'])->name('search');
Route::get('/favorites', [FavoritesController::class, 'index'])->name('favorites');
Route::get('/sale', [CatalogController::class, 'sale'])->name('catalog.sale');
```

**Личный кабинет** (префикс `/account`, middleware `auth`):
```php
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [CabinetController::class, 'cabinet'])->name('index');
    Route::get('stats', [CabinetController::class, 'stats'])->name('stats');
    
    // Магазин
    Route::prefix('store')->name('store.')->group(function () {
        Route::get('/', [Backend\SellerController::class, 'index'])->name('index');
        Route::get('create', [CabinetController::class, 'createShop'])->name('create');
        Route::post('create', [CabinetController::class, 'storeShop'])->name('store');
        
        // Товары (resource-подобная структура)
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [Backend\ProductController::class, 'index'])->name('index');
            Route::get('create', [Backend\ProductController::class, 'create'])->name('create');
            Route::post('/', [Backend\ProductController::class, 'store'])->name('store');
            Route::get('{product}', [Backend\ProductController::class, 'show'])->name('show');
            Route::get('{product}/edit', [Backend\ProductController::class, 'edit'])->name('edit');
            Route::put('{product}', [Backend\ProductController::class, 'update'])->name('update');
            Route::delete('{product}', [Backend\ProductController::class, 'destroy'])->name('destroy');
            // ...дополнительные методы
        });
    });
    
    // Пользователь
    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/', [CabinetController::class, 'userIndex'])->name('index');
        Route::get('edit', [Backend\UserProfileController::class, 'edit'])->name('edit');
        Route::put('/', [Backend\UserProfileController::class, 'update'])->name('update');
    });
    
    // Тарифы
    Route::prefix('packages')->name('packages.')->group(function () {
        Route::get('/', [Backend\PackageController::class, 'index'])->name('index');
        Route::post('{package}/purchase', [Backend\PackageController::class, 'purchase'])->name('purchase');
    });
});
```

**Админ-панель** (префикс `/admin`, middleware `only.my.email`):
```php
Route::group(['prefix' => 'admin', 'middleware' => ['web', 'auth', 'only.my.email']], function () {
    Route::get('activity-log', [ActivityLogController::class, 'index'])->name('admin.activity-log');
    Route::get('category-tree', [CategoryTreeController::class, 'index'])->name('admin.category-tree');
    Voyager::routes();
});
```

#### routes/api.php
- Минимальная конфигурация
- Один маршрут: `GET /api/user` (Sanctum auth)

---

### Jobs (app/Jobs/)

#### WarmCategoriesCache
**Назначение**: Прогрев (обновление) кеша категорий

**Что делает**:
1. Очищает кеши: `categories_root_tree`, `categories_root_list`, `menu_categories`
2. Пересчитывает дерево категорий
3. Фильтрует категории без товаров (если включено в конфиге)

**Когда запускается**:
- При изменении категорий
- При изменении товаров (если затронуты категории)

**Реализует**: `ShouldQueue` - асинхронное выполнение

---

### Listeners (app/Listeners/)

#### LogSuccessfulLogin
- **Событие**: `Illuminate\Auth\Events\Login`
- **Действие**: Запись в лог через Spatie Activity Log
- **Данные**: IP, guard

#### LogSuccessfulLogout
- **Событие**: `Illuminate\Auth\Events\Logout`
- **Действие**: Запись в лог

#### LogUserRegistered
- **Событие**: `Illuminate\Auth\Events\Registered`
- **Действие**: Запись в лог

---

### Middleware

#### OnlyMyEmail
**Назначение**: Ограничение доступа к админ-панели

**Логика**:
- Проверяет email пользователя против списка разрешенных
- Используется для защиты `/admin`

---

### Providers

#### AppServiceProvider
**Регистрация глобальных view-переменных**:
```php
View::share('categories', $menuCategories);
View::share('menuRootCategories', $menuCategories);
```

**Особенности**:
- Загрузка кешированного дерева категорий при каждом запросе
- Фильтрация пустых категорий для меню
- Try-catch для обработки ошибок при миграциях

#### AuthServiceProvider
- Регистрация политик (Policies)
- Настройка прав доступа (Gates)

---

## Система тарифов (Packages)

### Концепция

**Тарифные планы** (`packages`) определяют:
- Количество активных товаров (`active_products_limit`)
- Количество VIP товаров (`vip_products_limit`)
- Длительность действия (`duration_days`)
- Стоимость (`price`)

### Механизм работы

1. **Покупка тарифа**:
   - Продавец выбирает тариф
   - Средства списываются с баланса пользователя (`users.balance`)
   - Создается запись в `seller_packages`:
     - `is_active = true`
     - `start_date = today`
     - `end_date = today + duration_days`

2. **Активация товара**:
   - Проверяется наличие активного тарифа (`Seller::activePackage()`)
   - Подсчитывается количество активных товаров
   - Если лимит не достигнут → активация разрешена
   - Если лимит достигнут → возврат ошибки

3. **Автопродление** (`auto_renew`):
   - Флаг в таблице `sellers`
   - Если включено → система автоматически продлевает тариф за день до окончания
   - Списывает средства с баланса пользователя
   - Создает новую запись в `seller_packages`

4. **Истечение тарифа**:
   - Товары продавца автоматически скрываются из каталога
   - `Product::scopeVisible()` исключает товары с истекшими тарифами
   - Продавец может реактивировать товары после покупки нового тарифа

### Проверка лимитов

**Методы в модели Seller**:
```php
canActivateProduct(?int $excludeProductId): array
canActivateVipProduct(?int $excludeProductId): array
```

**Возвращают**:
```php
[
    'allowed' => bool,     // Можно ли активировать
    'message' => string,   // Сообщение пользователю
    'current' => int,      // Текущее количество
    'limit' => int,        // Максимальное количество
]
```

---

## Система кеширования

### Стратегия кеширования

**Категории**:
- `categories_root_tree` - полное дерево категорий (24 часа)
- `categories_root_list` - плоский список корневых категорий (24 часа)
- `menu_categories` - отфильтрованное дерево для меню (24 часа)
- `category_products_count:{id}` - количество товаров в категории (10 минут)

**Товары**:
- `product_price_range` - минимальная и максимальная цена (10 минут)

**Продавцы**:
- `seller_by_alias:{alias}` - продавец по alias (24 часа)
- `all_sellers` - список всех продавцов (24 часа)

### Очистка кеша

**При изменении товара** (`Product::clearProductCaches()`):
- Очищается `product_price_range`
- Очищается `menu_categories`
- Очищаются счетчики товаров категории и всех родителей
- Запускается Job `WarmCategoriesCache` для прогрева

**При изменении категории**:
- Очищаются все кеши категорий
- Запускается Job `WarmCategoriesCache`

---

## Обработка изображений

### Загрузка изображений

**Процесс**:
1. Загрузка файлов через форму (max 5 изображений)
2. Валидация: `image`, `mimes:jpeg,png,jpg,webp`, `max:5120`
3. Обработка через Intervention Image:
   - Создание миниатюры (сохранение пропорций)
   - Сжатие качества (80%)
4. Сохранение в `storage/app/public/products/`
5. Создание записи в таблице `images`

### Главное изображение

- Флаг `is_main` в таблице `images`
- Первое загруженное изображение автоматически становится главным
- Продавец может изменить главное изображение через `setMainImage()`

### Удаление изображений

- Удаление файла из storage
- Удаление записи из БД
- Если удалено главное → автоматически выбирается новое главное (первое из оставшихся)

---

## Логирование активности (Spatie Activity Log)

### Что логируется

**Модели**:
- `Product` - создание, обновление, удаление (поля: name, price, lowprice, is_vip, is_active, category_id, seller_id)
- `Seller` - создание, обновление, удаление (поля: name, phone, town, sellerplace, address, description)

**События аутентификации**:
- Успешный вход (`Login`)
- Выход (`Logout`)
- Регистрация (`Registered`)

### Просмотр логов

- Админ-панель: `/admin/activity-log`
- Доступ только для пользователей с разрешенным email

---

## Поиск и фильтрация

### Поиск товаров

**Механизм** (`CatalogController::search()`):
- Поиск по полям: `name`, `desc`, `features1-4`
- Использует `LIKE '%keyword%'`
- Фильтрация только по видимым товарам (`scopeVisible`)
- Сортировка: VIP → обычные → по `push_at`
- Пагинация: 12 товаров на страницу

### Фильтры каталога

**Доступные фильтры**:
- Категория (иерархическая)
- Продавец (dropdown)
- Диапазон цен (slider)

**Хранение фильтров**:
- Session (ключ: `catalog_filters`)
- Сохраняются между страницами
- Можно сбросить через `?clear_seller=1`

---

## Избранное (Favorites)

### Механизм работы

**Frontend (localStorage)**:
```javascript
let favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
```

**Backend (FavoritesController)**:
1. Получение массива ID из localStorage (через AJAX)
2. Загрузка товаров по ID: `Product::visible()->whereIn('id', $ids)`
3. Возврат данных в формате JSON или HTML (для AJAX)

**Особенности**:
- Нет аутентификации (работает для всех)
- Данные хранятся локально в браузере
- Не сохраняются между устройствами

---

## Оптимизация производительности

### N+1 проблема

**Решение**:
- Eager Loading: `with(['images', 'seller', 'category'])`
- Используется во всех контроллерах каталога

### Кеширование

**Долгосрочные кеши** (24 часа):
- Дерево категорий
- Список продавцов

**Короткие кеши** (10 минут):
- Счетчики товаров
- Диапазон цен

### Индексы БД

**Составные индексы**:
- `products`: `['is_active', 'is_vip', 'push_at']` - для сортировки каталога
- `categories`: `['_lft', '_rgt']` - для Nested Set запросов

### Nested Set (категории)

**Преимущества**:
- Получение всего дерева одним запросом (вместо рекурсивных)
- Быстрый поиск всех потомков: `WHERE _lft > X AND _rgt < Y`
- Быстрый поиск предков: `WHERE _lft < X AND _rgt > Y`

**Недостатки**:
- Сложность обновления структуры
- Требует пересчета `_lft` и `_rgt` при изменениях

---

## Настройки и конфигурация

### config/categories.php
```php
return [
    'hide_empty' => true,  // Скрывать категории без товаров
];
```

### config/cache.php
- Используется драйвер: `file` (по умолчанию)
- Рекомендуется: `redis` для production

### config/queue.php
- Job `WarmCategoriesCache` использует очередь
- По умолчанию: `sync` (синхронное выполнение)
- Рекомендуется: `redis` или `database` для production

---

## Тестирование

### Структура

- `tests/Feature/` - функциональные тесты (HTTP)
- `tests/Unit/` - модульные тесты
- `tests_manual/` - ручные тесты (не PHPUnit)

### Запуск

```bash
php artisan test
```

### Покрытие

**На текущий момент**: минимальное (проект в стадии разработки)

**Рекомендуется покрыть тестами**:
- Систему тарифов (лимиты, покупка, автопродление)
- CRUD товаров (проверка прав, валидация)
- Видимость товаров (scope `visible`)
- Кеширование категорий

---

## Безопасность

### Аутентификация

- Laravel Fortify (login, register, password reset)
- Session-based аутентификация
- CSRF-защита для всех POST/PUT/DELETE запросов

### Авторизация

**Проверки в контроллерах**:
- Middleware `auth` для личного кабинета
- Проверка владения ресурсом: `$user->seller->id === $product->seller_id`
- Middleware `only.my.email` для админ-панели

**Отсутствует**:
- Gates и Policies (не используются в текущей версии)

### Валидация

**Текущая реализация**:
- Валидация вручную через `Validator::make()` в контроллерах
- Кастомные сообщения об ошибках

**Проблемы**:
- Нет Form Request классов (несоответствие Laravel best practices)
- Логика валидации смешана с контроллерами

### XSS защита

- Blade автоматически экранирует вывод: `{{ $variable }}`
- Не используется `{!! $variable !!}` без необходимости

### SQL инъекции

- Eloquent ORM защищает от SQL-инъекций
- Используются prepared statements

---

## Развертывание

### Требования

- PHP 8.1+
- MySQL / PostgreSQL
- Composer
- Node.js + NPM (для компиляции assets)
- Redis (рекомендуется для cache и queue)

### Установка

```bash
# 1. Клонирование и установка зависимостей
git clone <repo>
cd project
composer install
npm install

# 2. Настройка окружения
cp .env.example .env
php artisan key:generate

# 3. Миграции и сидеры
php artisan migrate
php artisan db:seed

# 4. Линк для storage
php artisan storage:link

# 5. Компиляция assets
npm run build

# 6. Настройка очередей (для production)
php artisan queue:work

# 7. Настройка планировщика (для автопродления тарифов)
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

### Оптимизация для production

```bash
# Кеширование конфигурации
php artisan config:cache

# Кеширование маршрутов
php artisan route:cache

# Кеширование view
php artisan view:cache

# Оптимизация автозагрузки
composer dump-autoload --optimize
```

---

## Известные проблемы и технический долг

### Архитектурные проблемы

1. **Отсутствие Form Requests**
   - Валидация реализована в контроллерах
   - Нарушает Single Responsibility Principle
   - Затрудняет повторное использование правил

2. **Отсутствие `declare(strict_types=1);`**
   - Все PHP файлы не содержат строгой типизации
   - Снижает надежность кода

3. **Версия Laravel**
   - Используется Laravel 10, а в инструкции указан Laravel 12
   - Возможна несовместимость при обновлении

4. **Бизнес-логика в контроллерах**
   - Контроллеры содержат сложную бизнес-логику (особенно ProductController)
   - Рекомендуется вынести в Service или Action классы

5. **Отсутствие API**
   - Минимальная реализация API в `routes/api.php`
   - Нет полноценного REST API для мобильных приложений

### Недостающий функционал

1. **Тесты**
   - Минимальное покрытие тестами
   - Отсутствуют тесты для критичных компонентов

2. **Локализация**
   - Не все строки вынесены в языковые файлы
   - Смешаны русские строки в коде

3. **Policies**
   - Не используются Laravel Policies для авторизации
   - Проверки прав доступа разбросаны по контроллерам

4. **Events/Listeners**
   - Минимальное использование событий
   - Логика связана напрямую (tight coupling)

---

## Планы развития

### Краткосрочные (Technical Debt)

- [ ] Создать Form Request классы для всех форм
- [ ] Добавить `declare(strict_types=1);` во все PHP файлы
- [ ] Вынести бизнес-логику в Service/Action классы
- [ ] Создать Policies для авторизации
- [ ] Добавить тесты для критичных компонентов

### Среднесрочные

- [ ] Реализовать полноценный REST API
- [ ] Добавить систему заказов (корзина, оформление)
- [ ] Внедрить систему рейтингов и отзывов
- [ ] Добавить уведомления (email, push)
- [ ] Интегрировать платежные системы

### Долгосрочные

- [ ] Мобильное приложение (iOS/Android)
- [ ] Система чатов продавец-покупатель
- [ ] Аналитика и отчеты для продавцов
- [ ] Многоязычность
- [ ] Система рекомендаций (ML)

---

## Контакты и поддержка

**Разработчик**: [Указать имя/команду]  
**Email**: [Указать email]  
**Репозиторий**: [Указать ссылку на Git]

---

## Лицензия

MIT License

---

**Дата создания документации**: 03.06.2026  
**Версия документации**: 1.0  
**Версия проекта**: 1.0.0
