# Документация по проекту интернет-магазина

## 1. Общая архитектура и стек
- Бэкенд построен на Laravel 10 (см. `composer.json`, зависимость `laravel/framework` ^10.10) с PHP ^8.1.
- Используются Laravel Fortify для аутентификации и Filament 3 для административной панели.
- Дополнительные сервисы Laravel Sanctum, GuzzleHTTP для HTTP-запросов, Laravel Pint и PHPUnit в dev-зависимостях.
- Фронтенд собирается через Vite (`package.json`), клиентские зависимости ограничены `axios`, `laravel-vite-plugin`, `vite`.
- Вьюхи Blade лежат в `resources/views`, статические ассеты (CSS, JS, изображения) — в `public/`.

## 2. Структура каталогов (верхний уровень)
- `app/` — прикладной код Laravel: контроллеры, модели, сервисы, policies, observers, middleware, notifications, Fortify actions и провайдеры.
- `bootstrap/` и `config/` — стандартная конфигурация и загрузка фреймворка.
- `database/` — миграции, сиды, фабрики; ядро описания схемы таблиц и демо-данных.
- `public/` — собранные стили/скрипты (`css/`, `auth/assets/`, изображения) и точка входа `index.php`.
- `resources/` — Blade-шаблоны (в том числе `backend/` для кабинета), исходные `js`/`css`.
- `routes/` — файлы маршрутов (`web.php`, `api.php`, `console.php`, `channels.php`).
- `storage/`, `vendor/`, `node_modules/` — служебные директории.

## 3. Маршруты и middleware (routes/web.php)
- Главная (`/` и `/home`) → `HomeController@home`, view `home`.
- Каталог:
  - `GET /catalog` → `CatalogController@catalog`, view `catalog.index`.
  - `GET /catalog/{alias}` → `CatalogController@show`, view `catalog.show`.
  - `GET /sale` → `CatalogController@sale`, view `salepage`.
- Товары: `GET /product/{alias}` → `ProductController@show`, view `product`.
- Продавцы: `GET /shops` → `SellerController@shops`, view `sellers`; `GET /shops/{alias}` → `SellerController@seller`, view `seller`.
- Информационные страницы: `GET /gobuyer`, `GET /help` → `PageController`.
- Кабинет пользователя: `GET /account` → `CabinetController@cabinet` (middleware `auth`).
- Обновление профиля из кабинета: POST `cabinet/update-name`, `cabinet/update-password` (оба под `auth`).
- `/admin` обслуживается Filament и использует guard `web`, контракт `FilamentUser` и Gate `access-admin`.
- Зарезервированы маршруты `cabinet/create-shop` и `cabinet/store-shop`, но методы `CabinetController::createShop` и `::storeShop` пока не реализованы.

## 4. Контроллеры (`app/Http/Controllers`)
### HomeController
- `home()` — отдаёт шаблон `home`.

### PageController
- `gobuyer()` и `help()` — статичные страницы.

### ProductController
- `show($alias)` — получает товар по `alias` с загрузкой `images`, `seller`, `category`, формирует хлебные крошки и выводит `product`.

### SellerController
- `shops()` — все продавцы, сортировка по `product_count`, view `sellers`.
- `seller($alias)` — продавец, категории с подсчётом товаров продавца посредством вложенной функции `countSellerProductsInCategory`; результат отправляется в `seller`.

### CatalogController
- `catalog(Request)` — определяет глобальные `minPrice/maxPrice`, фильтры из сессии `catalog_filters.*`, собирает активные VIP и обычные товары, фильтрует по продавцу/диапазону цен, загружает дерево категорий (до 8 уровней), считает `categoryProductsCounts`, выводит `catalog.index`.
- `show($alias, Request)` — грузит категорию по алиасу, собирает список id дочерних категорий (`collectCategoryIds`), фильтрует товары внутри дерева, считает продавцов (`product_count_in_category`), хлебные крошки, возвращает `catalog.show`.
- `sale()` — активные товары со скидкой (`lowprice` не null), view `salepage`.
- `collectCategoryIds()` / `getCategoryProductsCount()` — приватная рекурсивная логика обхода категорий.

### CabinetController
- `cabinet()` — возвращает `backend.cabinet.cabinet` (дашборд-заглушка).
- `updateName()` — валидация `name`, сохранение и редирект с flash `success`.
- `updatePassword()` — проверка `current_password`, установка нового пароля или `error`.
- Методы `createShop`/`storeShop` отсутствуют, хотя маршруты объявлены.

### Прочие контроллеры
- `Controller.php` — базовый трейт `AuthorizesRequests`, `ValidatesRequests`.

## 5. Модели (`app/Models`)
- `User` расширяет стандартный Laravel `Authenticatable` и реализует `FilamentUser`: `fillable` — `name`, `email`, `phone`, `password`; скрывает `password`, `remember_token`; каст `password` => `hashed`; метод `sendPasswordResetNotification()` использует `ResetPasswordCustom`.
- `Seller`: хранит профиль и контакты магазина, связи `user()`, `products()`, `sellerPackages()`, запрос активного тарифа и совместимые методы кеша. Баланс принадлежит `User`, тарифные лимиты и UI-статус в модели отсутствуют.
- `Product`: связи `category()`, `seller()`, `images()`.
- `Category`: `fillable` (название, алиас, номер, описание, `parent_id`), связи `children()`, `parent()`, `products()`, аксессор `breadcrumbs` собирает цепочку родителей.
- `Image`: `fillable` (`product_id`, `path`, `is_main`), связь `product()`.

### Доменные сервисы продавца и тарифа
- `ProductLimitService` считает активные/VIP товары, проверяет лимиты тарифа и применяется при создании, редактировании, активации товара и покупке тарифа.
- `PackageService` покупает, продлевает и деактивирует тарифы в транзакциях; списание выполняется из `User.balance` после блокировки пользователя через `lockForUpdate()`.
- `SellerPackageStatusService` формирует данные для уведомлений кабинета о состоянии тарифа и возможности автопродления.
- `SellerSettingsService` изменяет настройку автопродления, `SellerProfileService` обновляет профиль магазина.

## 6. Провайдеры (`app/Providers`)
- `AppServiceProvider` при наличии таблицы `categories` шарит корневые категории во все вьюхи.
- `FortifyServiceProvider` регистрирует кастомные действия (раздел 7), задаёт rate limiting login/2FA, переназначает вьюхи Fortify на `backend.auth.*` и `backend.password.*`.
- `RouteServiceProvider` задаёт `HOME = '/account'`, подключает web/api маршруты.
- `AuthServiceProvider` регистрирует policies товаров и продавцов.
- `EventServiceProvider` регистрирует observers категорий, изображений, товаров, продавцов и тарифов продавца, а также слушатели аутентификации.

## 7. Действия Fortify (`app/Actions/Fortify`)
- `PasswordValidationRules` — общие правила (`required`, `Password::default()`, `confirmed`).
- `CreateNewUser` — валидация имени, уникального email, 8-значного телефона, создание пользователя.
- `UpdateUserProfileInformation` — обновление имени/email, сброс `email_verified_at` для MustVerifyEmail.
- `UpdateUserPassword` — проверка `current_password:web`, обновление пароля, ошибки пишутся в bag `updatePassword`.
- `ResetUserPassword` — сброс пароля по токену Fortify.

## 8. Middleware и Kernel
- `EnsureAdminAccess` разрешает административный доступ через application-owned permission `browse_admin`.
- `app/Http/Kernel.php` регистрирует стандартные группы `web`, `api` и alias `admin.access`.
- Gate `access-admin` объявлен в `AuthServiceProvider`.

## 9. Уведомления
- `ResetPasswordCustom` — mail-уведомление со ссылкой `route('password.reset')` и локализованными текстами (используется `User::sendPasswordResetNotification`).

## 10. Представления
- Общий макет витрины `resources/views/main_layots.blade.php` подключает `css/style.css`, `css/cabinet.css`, OwlCarousel, Slick и содержит хедер, боковое меню, футер.
- Ключевые Blade-файлы витрины: `home.blade.php`, `catalog/index.blade.php`, `catalog/show.blade.php`, `product.blade.php`, `sellers.blade.php`, `seller.blade.php`, `salepage.blade.php`, `gobuyer.blade.php`, `help.blade.php`. Они ожидают данные от соответствующих контроллеров.
- Кабинет (`backend/cabinet/layout_cabinet.blade.php` + `backend/cabinet/cabinet.blade.php`) содержит навигацию, logout-форму, статичные карточки KPI.
- Аутентификация Fortify использует `backend/auth/login_layouts.blade.php`, `backend/auth/login.blade.php`, `backend/auth/register.blade.php`, `backend/password/reset.blade.php`, `backend/password/set.blade.php`.
- Письма — `resources/views/vendor/mail/text/*.blade.php`.

## 11. Ресурсы (CSS/JS)
- `public/css/` хранит стили витрины (`style.css`, `tarif.css`, `cabinet.css`, `bootstrap-slider.css` и др.).
- `resources/js/bootstrap.js` регистрирует axios и содержит пример настройки Laravel Echo (закомментирован).
- `resources/css/app.css` пока пустой.

## 12. База данных и связи
- Стандартные таблицы: `users`, `password_reset_tokens`, `failed_jobs`, `personal_access_tokens`, расширенные полями телефона и 2FA.
- Категории (`categories`): `alias` unique, `parent_id` (self FK, `set null`), `is_active`.
- Пакеты (`packages`): цена, длительность, лимиты активных и VIP товаров, `is_active`.
- Продавцы (`sellers`): FK `user_id`, контакты, `alias` unique, `product_count`, `can_order`; баланс хранится в `users`.
- Товары (`products`): описание, `alias` unique, `is_vip`, `is_active`, `sale_started_at`, `push_at`, FK `category_id`, `seller_id`.
- Изображения (`images`): FK `product_id`, `path`, `alias`, `alt`, `order`.
- Просмотры (`views`): `views`, `product_id`.
- Связки пакетов (`seller_packages`): `seller_id`, `package_id`, даты `start_date`/`end_date`, `is_active`.

## 13. Сиды (database/seeders)
- `DatabaseSeeder` создаёт application-owned роли и permissions, затем запускает доменные сиды (`AdminRoleSeeder`, `UsersTableSeeder`, `CategoriesTableSeeder`, `PackagesTableSeeder`, `SellersTableSeeder`, `ProductsTableSeeder`, `ImagesTableSeeder`, `SellerPackagesTableSeeder`).
- `AdminRoleSeeder` создаёт роль admin и permission `browse_admin`; пользователь задаётся через `ADMIN_EMAIL`, а новый пароль — через `ADMIN_PASSWORD`.
- `UsersTableSeeder` — пользователи `seller1@example.com`, `seller2@example.com` с ролью user.
- `CategoriesTableSeeder` — трёхуровневое дерево категорий (примерные названия).
- `SellersTableSeeder` — два продавца с описанием и ссылками.
- `PackagesTableSeeder` — тарифы («Бесплатный», «Старт» и др.).
- `ProductsTableSeeder` — демо-товары (часть VIP), расставляет `push_at`, `sale_started_at`.
- `ImagesTableSeeder` — изображения для товаров.
- `SellerPackagesTableSeeder` — активные подписки продавцов.

## 14. Кабинет продавца
- Fortify перенаправляет на `/account` (см. `RouteServiceProvider::HOME`).
- `CabinetController@cabinet` пока возвращает статичную Blade-разметку; для данных продавца нужно расширять метод.
- POST-маршруты обновления имени/пароля функционируют, но формы в текущих шаблонах отсутствуют.
- Стили кабинета расположены в `public/css/cabinet.css`.
- Маршруты `cabinet/create-shop`, `cabinet/store-shop` требуют реализации методов и шаблонов.

## 15. Filament
- `/admin` обслуживается Filament; доступ требует Gate `access-admin`.
- Voyager и его BREAD-инфраструктура полностью удалены.
- Ресурсы Filament управляют пользователями, продавцами, товарами, категориями, тарифами и подписками.
- Изменение баланса, ролей и других критичных данных выполняется через отдельные действия и сервисы.

## 16. Дополнительные заметки
- В `CatalogController` импортирован `Cache`, но он не используется.
- Тексты в некоторых Blade и сидерах содержат «битые» символы из-за кодировки Windows-1251; стоит перевести файлы в UTF-8.
- Рекомендуется выполнять `php artisan migrate --seed`, чтобы `AppServiceProvider` корректно делился категориями.
- Для кабинета пригодится связь `User hasOne Seller`, чтобы не искать продавца вручную.
- Перед релизом важно реализовать `createShop`/`storeShop` и добавить формы в кабинете.
