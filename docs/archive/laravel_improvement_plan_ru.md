# Архив: план улучшения Laravel-проекта

## Компенсирующая защита email

На 08.06.2026 без обновления Laravel добавлена защита от CRLF-инъекции:

- единое правило `SafeEmail` запрещает `\r` и `\n`;
- правило применяется при регистрации и изменении профиля;
- Filament запрещает переносы строк в email пользователя;
- `User::routeNotificationForMail()` повторно проверяет адрес перед отправкой уведомления;
- защита покрыта unit, feature и Filament-тестами;
- полный набор: 376 тестов, 1252 assertions.

Это снижает риск в приложении, но не снимает Composer advisory с Laravel 10.

Дата составления: 04.06.2026

Цель: улучшить проект без переписывания с нуля. План рассчитан на последовательную работу, где каждый этап можно выполнить, протестировать и только потом переходить дальше.

## Выполнено 04.06.2026

- Исправлен `AppServiceProvider`: категории для меню теперь загружаются лениво через view composer, поэтому `php artisan route:list` и `php artisan test` больше не требуют подключения к MySQL при boot приложения.
- Добавлен тест `RouteListBootTest`, который проверяет запуск `route:list` при недоступной БД.
- Исправлен счетчик просмотров товара: вместо `updateOrInsert` с `DB::raw('views + 1')` используется `increment` с явной вставкой первой записи.
- Добавлен тест на повторное увеличение счетчика просмотров товара.
- Debug-маршрут `/test-package-system` закрыт для всех окружений кроме `local`, добавлен тест недоступности в testing.
- Проверка после изменений: `php artisan test` - 158 passed, 274 assertions.
- Валидация товара вынесена в `ProductRequest`, `StoreProductRequest`, `UpdateProductRequest`.
- `Backend\ProductController::store` и `Backend\ProductController::update` теперь получают данные через `$request->validated()`, а доменные проверки лимитов пока оставлены в контроллере.
- Добавлен `ProductRequestTest` на базовые правила товара и общий набор правил store/update.
- Проверка после этапа FormRequest: `php artisan test` - 162 passed, 280 assertions.
- Обработка изображений товара вынесена в `ProductImageService`: загрузка webp, удаление файлов, удаление изображения и назначение главного изображения больше не находятся в backend-контроллере.
- `Backend\ProductController` подключен к `ProductImageService`; контроллер теперь отвечает за HTTP-сценарий, а файловая логика изолирована в сервисе.
- Добавлен `ProductImageServiceTest`: проверяет создание файлов и записей, лимит изображений, удаление главного изображения с продвижением следующего и переключение главного изображения.
- Проверка после этапа ProductImageService: `php artisan test` - 166 passed, 295 assertions.
- Логика применения лимитов товара вынесена в `ProductLimitService`: создание, обновление и активация товара используют единый сервис вместо ручного дублирования проверок в контроллере.
- Исправлена ошибка внедрения зависимостей: `Backend\ProductController::store` и `Backend\ProductController::update` теперь явно получают `ProductImageService`, который уже использовался внутри методов.
- Добавлен `ProductLimitServiceTest`: проверяет неактивные товары, обязательные изображения для публикации, лимит активных товаров, VIP-лимит, исключение текущего товара при редактировании и приоритет ошибки отсутствующих изображений при активации.
- Проверка после этапа ProductLimitService: `php artisan test` - 172 passed, 305 assertions.
- Покупка, продление и деактивация тарифов вынесены в `PackageService`; транзакции, списание баланса, проверка лимитов и переключение активного пакета теперь находятся в сервисном слое.
- `Backend\PackageController` упрощен до HTTP-обработки: проверяет наличие магазина, вызывает `PackageService::purchase`, пишет activity log и возвращает redirect.
- `packages:deactivate-expired` переведен на `PackageService` и исправлен источник баланса: команда теперь использует `seller.user.balance`, а не удаленное поле `seller.balance`.
- `Seller::tryAutoRenewPackage` использует `PackageService::renew`, чтобы автопродление не дублировало транзакционную логику модели.
- Добавлены `PackageServiceTest` и `DeactivateExpiredPackagesCommandTest`: покрывают покупку тарифа, rollback при нехватке средств, лимиты активных/VIP товаров, продление из баланса пользователя и деактивацию просроченного пакета.
- Проверка после этапа PackageService: `php artisan test` - 180 passed, 331 assertions.
- Создание магазина вынесено из `CabinetController` в `ShopService`: генерация alias, создание продавца, назначение базового тарифа и обработка логотипа больше не смешаны с HTTP-логикой.
- Добавлен `StoreShopRequest` для валидации формы создания магазина.
- Назначение базового тарифа теперь выполняется в транзакции; если базовый тариф отсутствует, продавец не создается частично.
- Local debug-маршрут `/test-package-system` больше не обращается к удаленному `seller.balance`, а использует `seller.user.balance`.
- Добавлен `ShopServiceTest`: проверяет создание магазина с базовым тарифом, уникальный alias, rollback без базового тарифа и сохранение logo в webp.
- Проверка после этапа ShopService: `php artisan test` - 184 passed, 342 assertions.
- Статистика кабинета вынесена из `CabinetController::stats` в `CabinetStatsService`; контроллер теперь только проверяет наличие магазина и передает данные во view.
- Добавлен `CabinetStatsServiceTest`: проверяет счетчики товаров, просмотры, top-5 товаров, активный тариф, лимиты, историю тарифов, баланс и пустой магазин.
- `CabinetControllerTest` расширен проверкой `account.stats`, чтобы зафиксировать передачу данных сервиса во view.
- Проверка после этапа CabinetStatsService: `php artisan test` - 187 passed, 373 assertions.
- Смена пароля и удаление магазина вынесены из `CabinetController` в `AccountSecurityService`; проверка текущего пароля и хеширование больше не находятся в контроллере.
- Добавлены `UpdatePasswordRequest` и `DestroyShopRequest` для валидации форм смены пароля и удаления магазина.
- Добавлен `AccountSecurityServiceTest`: проверяет успешную смену пароля, отказ при неверном текущем пароле, удаление магазина с каскадным удалением тарифов и отказ при неверном пароле.
- `CabinetControllerTest` расширен сценариями удаления магазина: успешное удаление, неверный пароль и обязательность пароля.
- Проверка после этапа AccountSecurityService: `php artisan test` - 194 passed, 393 assertions.
- Переключение автопродления вынесено из `CabinetController::toggleAutoRenew` в `SellerSettingsService`.
- Добавлен `ToggleAutoRenewRequest` для валидации JSON/форм-поля `auto_renew`.
- Добавлен `SellerSettingsServiceTest`, а `CabinetControllerTest` расширен сценариями включения, выключения и ошибки 404 при отсутствии магазина.
- Проверка после этапа SellerSettingsService: `php artisan test` - 199 passed, 405 assertions.
- Обновление данных магазина вынесено из `Backend\SellerController::update` в `SellerProfileService`; обработка логотипа и удаление старого файла больше не находятся в контроллере.
- Добавлен `UpdateSellerRequest` для валидации формы редактирования магазина, включая уникальность имени с `ignore` текущего магазина.
- Добавлены `SellerProfileServiceTest` и `BackendSellerControllerTest`: покрывают обновление полей, замену логотипа, удаление старого файла, запрет обновления чужого магазина и redirect при отсутствии магазина.
- Проверка после этапа SellerProfileService: `php artisan test` - 206 passed, 429 assertions.

- Публичный `SellerController` переведен на `SellerPageService`: список магазинов, страница продавца, AJAX-догрузка магазинов и товаров теперь используют единый сервисный слой вместо запросов внутри контроллера.
- `SellerPageService` сохраняет текущую бизнес-логику: сортировка магазинов по количеству активных товаров, показ только видимых товаров продавца, фильтрация пустых корневых категорий и расчет `total_products`.
- Добавлен `SellerPageServiceTest`: проверяет порядок магазинов, выборку только видимых товаров конкретного продавца и фильтрацию/счетчики категорий.
- `SellerControllerTest` расширен проверками partial-ответов для `load-more` магазинов и товаров продавца.
- Проверка после этапа SellerPageService: `php artisan test` - 211 passed, 440 assertions.
- Публичный `CatalogController` переведен на `CatalogPageService`: общий каталог, страница категории, распродажа, поиск и AJAX-догрузка теперь используют общий сервисный слой.
- Дублированная логика товарных запросов, фильтра продавца, ценового фильтра, category ids, меню категорий, счетчиков и продавцов категории вынесена из контроллера.
- Исправлено применение ценового фильтра в текущем запросе: `min_price`/`max_price` теперь сразу влияют на выборку, а не только сохраняются в сессию для следующего запроса.
- Добавлен `CatalogPageServiceTest`: проверяет текущий ценовой фильтр, фильтр продавца, выборку категории, распродажу и поиск по тексту/категории.
- `CatalogControllerTest` расширен проверками partial-ответов для `load-more` каталога, категории, распродажи и поиска.
- Проверка после этапа CatalogPageService: `php artisan test` - 220 passed, 461 assertions.
- `FavoritesController` переведен на `FavoritesService`: парсинг id из localStorage, лимит входных id, первичная загрузка, пагинация и выборка видимых товаров больше не находятся в контроллере.
- Сохранен прежний контракт эндпоинтов избранного: `/favorites/products` возвращает JSON с `html`, `has_more`, `total`, `last_page`, а `/favorites/load-more` возвращает HTML partial.
- Улучшена стабильность выдачи избранного: сервис возвращает товары в порядке id, пришедших с клиента, и не показывает невидимые товары.
- Добавлены `FavoritesServiceTest` и `FavoritesControllerTest`: покрывают очистку входных id, лимит 200 id, metadata первой страницы, offset следующей страницы, JSON-ответ и HTML-догрузку.
- Проверка после этапа FavoritesService: `php artisan test` - 228 passed, 478 assertions.
- Начат безопасный разбор большого `Backend\ProductController`: подготовка списка товаров продавца, дерева категорий, корневых категорий форм, выбранного пути категории, генерация уникального alias и AJAX дочерних категорий вынесены в `BackendProductService`.
- `Backend\ProductController` стал тоньше в методах `index`, `create`, `edit`, частично `store`/`update` и `getChildCategories`; загрузка изображений, лимиты и update/store сценарии оставлены на следующих подэтапах, чтобы не смешивать риски.
- Добавлены `BackendProductServiceTest` и `BackendProductControllerProductsTest`: покрывают уникальный alias, selected path, фильтр списка по категории, JSON дочерних категорий, view data списка и форму создания товара.
- Проверка после этапа BackendProductService: `php artisan test` - 235 passed, 499 assertions.
- Создан и явно зарегистрирован `ProductPolicy` с abilities `view`, `update`, `delete`, `activate`, `deactivate`, `manageImages`, `pushUp`.
- В `Backend\ProductController` удалены все ручные проверки `$product->seller->user_id !== Auth::id()`; просмотр, редактирование, удаление, публикация, снятие с публикации, изображения и поднятие товара используют `$this->authorize()`.
- Добавлен `ProductPolicyTest`: проверяет разрешения владельца, запреты для другого пользователя и ответы 403 всех защищенных backend-маршрутов товара.
- Проверка после этапа ProductPolicy: `php artisan test` - 238 passed, 521 assertions.
- Создан и зарегистрирован `SellerPolicy` с abilities `view`, `update`, `delete`, `manageSettings`, `purchasePackage`.
- Ручные проверки владельца магазина заменены на `$this->authorize()` в `Backend\SellerController` и `Backend\PackageController`; в `CabinetController` policy применяется для кабинета, статистики, удаления магазина и автопродления.
- Добавлен `SellerPolicyTest`: проверяет все разрешения владельца, запреты для другого пользователя и ответы 403 backend-маршрутов редактирования магазина.
- Проверка после этапа SellerPolicy: `php artisan test` - 241 passed, 533 assertions.
- Создание и обновление товара вынесены из `Backend\ProductController` в `ProductWriteService`: конечная категория, тарифные лимиты, boolean-флаги, alias, даты скидки, запись товара и добавление изображений теперь координируются сервисом.
- Удален искусственный `Validator::make([], [])`; доменные ошибки сервиса возвращаются через стандартный `ValidationException` с сохранением пути выбранной категории.
- Исправлен риск частичного обновления: лимит новых изображений проверяется до `$product->update()`, поэтому при переполнении лимита данные товара не сохраняются частично.
- Добавлен `ProductWriteServiceTest`, а `BackendProductControllerProductsTest` расширен сценариями создания и обновления товара через HTTP.
- Проверка после этапа ProductWriteService: `php artisan test` - 247 passed, 556 assertions.
- Активация, деактивация и поднятие товара вынесены из `Backend\ProductController` в `ProductActionService`.
- Ошибки активации и 24-часового cooldown оформлены типизированными `ProductActivationException` и `ProductPushTooSoonException`; контроллер сохраняет прежние JSON/redirect-контракты.
- Время успешного поднятия теперь возвращается из сохраненной модели, что исключает расхождение микросекунд между ответом и значением в БД.
- Добавлен `ProductActionServiceTest`, а `BackendProductControllerProductsTest` расширен AJAX-сценариями успешной/ошибочной активации, деактивации и повторного поднятия.
- Проверка после этапа ProductActionService: `php artisan test` - 256 passed, 580 assertions.
- Удаление товара, удаление изображения и назначение главного изображения перенесены из `Backend\ProductController` в `ProductActionService`.
- Запрет удаления последнего изображения активного товара оформлен через `ProductImageDeletionException`; контроллер сохраняет прежние JSON/redirect-ответы.
- `ProductActionServiceTest` и `BackendProductControllerProductsTest` расширены сценариями удаления товара и файлов, запрета последнего изображения, продвижения нового главного изображения и AJAX-назначения главного изображения.
- Проверка этапа ProductActionService images: `php -l` для измененных PHP-файлов пройден; PHPUnit-тесты не запускались по указанию пользователя и должны быть выполнены позже.
- Создан `CatalogCacheService`, который централизует ключи, TTL, чтение и точечную инвалидацию кеша категорий, меню, диапазона цен, продавцов и счетчиков товаров.
- `Category`, `Product`, `Seller`, `SellerPackage`, `CatalogPageService`, `AppServiceProvider` и `WarmCategoriesCache` переведены на единый сервис без изменения существующих публичных методов моделей и имен ключей.
- Добавлен `CatalogCacheServiceTest`: фиксирует повторное использование закешированных значений, изоляцию alias продавцов и полную/точечную очистку ключей.
- Проверка этапа CatalogCacheService: `php -l` для измененных PHP-файлов пройден; PHPUnit-тесты не запускались по указанию пользователя и должны быть выполнены позже.

## Проверка 06.06.2026

- Отложенные PHPUnit-тесты запущены полным набором: `267 passed`, `616 assertions`.
- `php artisan route:list --json` выполнен успешно, зарегистрировано 165 маршрутов.
- Laravel корректно загружается в локальном окружении: Laravel 10.48.29, PHP 8.4.1, maintenance mode выключен.
- Повторная проверка `php -l` измененных PHP-файлов не обнаружила синтаксических ошибок.
- Регрессии после внедрения policies, товарных сервисов и `CatalogCacheService` не обнаружены.
- Инвалидация кеша вынесена из model `booted()` в `ProductObserver`, `SellerObserver`, `CategoryObserver`, `SellerPackageObserver` и `ImageObserver`; observers зарегистрированы в `EventServiceProvider`.
- Публичные методы `Product::clearProductCaches()`, `Seller::clearSellersCache()` и `Category::clearCategoriesCache()` сохранены как совместимые фасады над `CatalogCacheService`.
- Исправлена инвалидация при переносе товара: теперь очищаются счетчики старой и новой категории, а также кеши старого и нового продавца.
- Изменение тарифа продавца теперь очищает category counts категорий его товаров, поскольку видимость товаров зависит от активности тарифа.
- Добавлен `CacheObserverTest` для переноса товара, смены alias продавца и изменения тарифа.
- Проверка после этапа observers: `270 passed`, `627 assertions`; `route:list` успешно регистрирует 165 маршрутов.
- Модель `Seller` сокращена с 435 до 136 строк: из нее удалены тарифные лимиты, подсчеты активных/VIP товаров, автопродление, UI-статус тарифа и склонение слов.
- Вся логика лимитов и подсчетов перенесена в `ProductLimitService`; `PackageService` получает его через dependency injection и использует тот же источник правил при покупке тарифа.
- Создан `SellerPackageStatusService`, который формирует данные для Blade-компонента статуса тарифа и использует только `User.balance`.
- Blade-шаблоны кабинета и local debug-маршрут переведены с методов модели на сервисы.
- Проверено отсутствие обращений к удаленному `Seller.balance`; миграции фиксируют перенос поля в `users`, а списание по-прежнему выполняется через `PackageService` с `lockForUpdate()`.
- Тесты лимитов перенесены с модели на `ProductLimitService`; добавлены feature-тесты статуса бессрочного/истекающего тарифа и достаточного/недостаточного баланса пользователя.
- Проверка после упрощения `Seller`: `273 passed`, `641 assertions`; Blade успешно компилируется, `route:list` регистрирует 165 маршрутов.
- Hardcoded email удалён из runtime-доступа к `/admin`: middleware `EnsureAdminAccess` использует Gate `access-admin` и application-owned permission `browse_admin`.
- `AdminRoleSeeder` больше не содержит email и пароль по умолчанию; первоначальный администратор задаётся через `ADMIN_EMAIL`, `ADMIN_NAME`, `ADMIN_PASSWORD`.
- Историческая защита Voyager BREAD заменена ресурсами Filament, policies и специализированными сервисами.
- Добавлены тесты административного доступа, ролей и критичных действий Filament.
- Проверка после этапа админ-доступа: `279 passed`, `664 assertions`; конфигурация кешируется, `route:list` регистрирует 165 маршрутов.

## Этап 1. Стабилизировать окружение и artisan-команды

### 1.1. Исправить `AppServiceProvider`

Проблема: `AppServiceProvider::boot()` обращается к базе данных при загрузке приложения. Из-за этого `php artisan route:list` и `php artisan test` падают, если MySQL недоступен.

Что сделать:

- убрать обязательные запросы к БД из глобального `boot()`;
- не вызывать `Schema::hasTable()` на каждом запуске приложения;
- вынести подготовку меню категорий во View Composer, который выполняется только для нужных Blade-шаблонов;
- для console/testing окружения возвращать пустые коллекции или лениво загружать категории только при реальном рендере view.

Ожидаемый результат:

- `php artisan route:list` работает без подключения к MySQL;
- `php artisan test` стартует на sqlite in-memory из `phpunit.xml`;
- artisan-команды не ломаются из-за недоступной production-БД.

### 1.2. Проверить базовые команды

После исправления выполнить:

```bash
php artisan route:list
php artisan test
php artisan config:clear
php artisan cache:clear
```

Если тесты падают уже внутри сценариев, фиксировать только ошибки, связанные с текущим этапом. Не смешивать это с рефакторингом контроллеров.

## Этап 2. Убрать временный и опасный код

### 2.1. Удалить тестовый web-маршрут

В `routes/web.php` есть публичный маршрут `/test-package-system`. Его нужно удалить или закрыть локальным окружением:

```php
if (app()->environment('local')) {
    Route::get('/test-package-system', ...);
}
```

Для production лучше удалить полностью.

### 2.2. Убрать устаревшие файлы

Проверить и удалить или перенести вне production-кода:

- `app/Http/Controllers/CatalogController.php.old`;
- `database/seeders/CategoriesTableSeeder.php.old`;
- `test_cache.php`;
- `LOGGG.txt`;
- `prepareBindings($bindings)`;
- временные файлы из `tests_manual`, если они не нужны.

Перед удалением проверить, что файлы не используются:

```bash
rg "CatalogController.php.old|CategoriesTableSeeder.php.old|test_cache"
```

## Этап 3. Вынести валидацию в Form Request

### 3.1. Создать request-классы для товаров

Создать:

- `app/Http/Requests/StoreProductRequest.php`;
- `app/Http/Requests/UpdateProductRequest.php`.

Перенести туда:

- правила `name`, `price`, `lowprice`, `category_id`, `desc`, `features*`, `is_vip`, `is_active`, `images`;
- пользовательские сообщения валидации;
- базовую авторизацию владельца, если request применяется к существующему товару.

Контроллер должен получить уже валидированные данные:

```php
$data = $request->validated();
```

### 3.2. Создать request-классы для магазина и профиля

Создать:

- `StoreSellerRequest`;
- `UpdateSellerRequest`;
- `UpdateUserProfileRequest`;
- `UpdatePasswordRequest`, если логика пароля остается в текущем кабинете.

Результат: контроллеры станут короче, а правила будут проще тестировать.

## Этап 4. Вынести обработку изображений

### 4.1. Создать сервис изображений товара

Создать `app/Services/ProductImageService.php`.

Ответственность сервиса:

- принимать uploaded files;
- валидировать лимит количества изображений;
- конвертировать в WebP;
- сохранять в storage;
- создавать записи `Image`;
- назначать главное изображение;
- удалять файл и запись;
- переустанавливать главное изображение после удаления.

Контроллер не должен знать детали `Intervention\Image`, путей и формата WebP.

### 4.2. Создать сервис логотипа продавца

Создать `app/Services/SellerLogoService.php`.

Вынести из `CabinetController` и `Backend\SellerController` обработку логотипа магазина, чтобы не дублировать код.

## Этап 5. Вынести тарифную бизнес-логику

### 5.1. Создать `PackageService`

Создать `app/Services/PackageService.php`.

Методы:

- `purchase(Seller $seller, Package $package): SellerPackage`;
- `deactivateExpired(bool $autoRenew): PackageCheckResult`;
- `tryAutoRenew(SellerPackage $sellerPackage): RenewalResult`;
- `deactivate(SellerPackage $sellerPackage): void`.

Из `Backend\PackageController` и `DeactivateExpiredPackages` убрать прямую транзакционную логику.

### 5.2. Создать `ProductLimitService`

Создать `app/Services/ProductLimitService.php`.

Методы:

- `canActivateProduct(Seller $seller, ?Product $exclude = null): LimitResult`;
- `canActivateVipProduct(Seller $seller, ?Product $exclude = null): LimitResult`;
- `validateProductState(Seller $seller, bool $isActive, bool $isVip, ?Product $exclude = null): array`.

После этого модель `Seller` перестанет быть сервисом тарифов.

## Этап 6. Ввести Policies

### 6.1. Создать policy для товаров

Создать:

```bash
php artisan make:policy ProductPolicy --model=Product
```

Правила:

- `view`;
- `update`;
- `delete`;
- `activate`;
- `deactivate`;
- `manageImages`;
- `pushUp`.

В контроллерах заменить ручные проверки:

```php
if ($product->seller->user_id !== Auth::id()) {
    abort(403);
}
```

на:

```php
$this->authorize('update', $product);
```

### 6.2. Создать policy для магазина

Создать `SellerPolicy` и вынести проверки владения магазином из `CabinetController`, `Backend\SellerController`, `Backend\PackageController`.

## Этап 7. Упростить контроллеры

### 7.1. Целевой размер контроллеров

Практический ориентир:

- обычный controller: до 150-200 строк;
- сложный resource controller: до 250 строк;
- если больше, почти наверняка нужен service/action.

Приоритет:

1. `Backend\ProductController`;
2. `CatalogController`;
3. `CabinetController`;
4. `Backend\PackageController`;
5. `Backend\SellerController`.

### 7.2. Разделить публичный каталог

Для каталога создать сервисы:

- `CatalogQueryService` - построение запросов товаров;
- `CategoryMenuService` - дерево категорий и счетчики;
- `SellerFilterService` - список продавцов для фильтров.

В `CatalogController` оставить:

- чтение request;
- вызов сервисов;
- возврат view.

## Этап 8. Централизовать кеширование

### 8.1. Создать `CatalogCacheService`

Ответственность:

- ключи кеша;
- TTL;
- очистка связанных кешей;
- прогрев категорий;
- инвалидация при изменении товара, категории, продавца, пакета.

Пример методов:

- `rememberMenuCategories()`;
- `rememberRootCategories()`;
- `rememberSellerList()`;
- `rememberPriceRange()`;
- `forgetProductRelated(Product $product)`;
- `forgetSellerRelated(Seller $seller)`;
- `forgetCategoryRelated(Category $category)`.

После этого убрать `Cache::forget()` из моделей, где это возможно, или заменить на события/listeners.

### 8.2. Перейти от model boot к observers

Создать:

- `ProductObserver`;
- `SellerObserver`;
- `CategoryObserver`;
- `SellerPackageObserver`.

В observers вызывать сервис кеширования. Это чище, чем держать инвалидацию внутри моделей.

## Этап 9. Улучшить доменную модель

### 9.1. Упростить `Seller`

Из `Seller` вынести:

- лимиты активных товаров;
- лимиты VIP;
- автопродление;
- пользовательские сообщения о тарифе;
- склонение слов для UI.

В модели оставить:

- `$fillable`, `$casts`;
- связи;
- простые scope/accessor;
- минимальные методы вроде `hasActivePackage()`, если они не тянут сложную логику.

### 9.2. Проверить баланс

В миграциях видно перенос баланса из `sellers` в `users`, но в части кода есть обращения к `$seller->balance`. Нужно привести модель оплаты к одному источнику истины.

Рекомендуемый вариант:

- баланс принадлежит `User`;
- `Seller` не хранит баланс;
- все списания идут через сервис оплаты/пакетов с блокировкой `users` через `lockForUpdate()`.

## Этап 10. Привести админку и доступы к Laravel-подходу

Статус: выполнено 06.06.2026.

### 10.1. Убрать hardcoded email

Вместо массива email используется:

- permission Voyager `browse_admin`;
- Gate `access-admin`;
- middleware `admin.access`.

### 10.2. Проверить Voyager BREAD

Проверить:

- какие модели доступны из Voyager;
- какие поля можно редактировать;
- защищены ли чувствительные поля;
- нет ли возможности редактировать баланс без аудита;
- нет ли возможности назначать себе роль администратора.

Результат проверки:

- в текущей БД зарегистрированы только `users`, `menus`, `roles`;
- `balance` не экспонируется в users BREAD;
- изменение `role_id` и role relationships отключено;
- изменение пароля существующего пользователя через BREAD отключено;
- создание администратора выполняется только через явно заданные env-переменные.

## Этап 11. Улучшить тесты

### 11.1. Сначала восстановить запуск

Первый критерий качества:

```bash
php artisan test
```

должен стартовать без MySQL и использовать sqlite in-memory из `phpunit.xml`.

### 11.2. Добавить тесты на рефакторинг

Перед выносом логики добавить или расширить тесты:

- создание товара;
- обновление товара;
- запрет активного товара без изображения;
- лимит активных товаров;
- лимит VIP;
- покупка тарифа;
- автопродление тарифа;
- очистка кеша после изменения товара;
- доступ владельца и не-владельца.

### 11.3. Тестировать сервисы отдельно

После создания сервисов часть feature-тестов можно дополнить unit-тестами сервисов. Это позволит менять UI без переписывания доменной логики.

## Этап 12. Улучшить frontend-слой Blade

### 12.1. Разделить большие шаблоны

Большие Blade-файлы лучше разбить на components/partials:

- карточка товара;
- фильтр категорий;
- фильтр продавцов;
- кнопка load-more;
- alert статуса пакета;
- блок статистики;
- форма товара;
- блок изображений товара.

### 12.2. Стандартизировать AJAX

Сейчас часть AJAX возвращает HTML partials. Это допустимо, но нужно выбрать единый подход:

- либо HTMX-like подход: сервер возвращает HTML;
- либо JSON API + render на клиенте.

Для текущего проекта проще оставить HTML partials, но документировать это как сознательное решение.

## Этап 13. Навести порядок в документации

### 13.1. Обновить основную документацию

`docs/project_documentation_ru.md` частично устарел: в нем есть сведения, которые уже не совпадают с текущими маршрутами и кабинетом.

Нужно обновить:

- маршруты кабинета;
- Backend-контроллеры;
- модели `Package` и `SellerPackage`;
- кеширование;
- тесты;
- scheduled command;
- текущие риски.

### 13.2. Добавить архитектурную карту

Создать отдельный документ:

- публичная витрина;
- кабинет продавца;
- админка Voyager;
- тарифная система;
- кеш;
- тесты;
- фоновые задачи.

## Этап 14. Рекомендуемый порядок выполнения

1. Исправить `AppServiceProvider`, чтобы artisan и тесты запускались стабильно.
2. Удалить или закрыть временный маршрут `/test-package-system`.
3. Убрать `.old` и временные файлы из production-кода.
4. Вынести request validation для товаров.
5. Вынести обработку изображений в сервис.
6. Вынести тарифы и лимиты в сервисы.
7. Добавить policies для `Product` и `Seller`.
8. Упростить `Backend\ProductController`.
9. Упростить `CatalogController`.
10. Централизовать кеширование через service + observers.
11. Обновить документацию.
12. Запустить полный набор тестов и исправить регрессии.

## 15. Что не стоит делать

- Не переписывать проект на SPA без явной бизнес-потребности.
- Не выносить все в микросервисы.
- Не удалять Voyager, пока он закрывает админские задачи.
- Не делать масштабный рефакторинг без тестов.
- Не менять структуру базы без миграционного плана.
- Не объединять несколько крупных этапов в один pull request.

## 16. Минимальный критерий успеха

После первых этапов проект должен соответствовать минимуму:

- `php artisan route:list` работает;
- `php artisan test` запускается;
- временный публичный тестовый маршрут удален;
- создание и редактирование товара используют FormRequest;
- обработка изображений вынесена из контроллера;
- покупка тарифа вынесена в сервис;
- ручные проверки владельца заменены policy хотя бы для товаров;
- документация отражает актуальное состояние проекта.
