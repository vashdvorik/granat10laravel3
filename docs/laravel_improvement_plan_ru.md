# План улучшения Laravel-проекта

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

### 10.1. Убрать hardcoded email

Вместо массива email в `OnlyMyEmail` использовать:

- роли Voyager;
- permissions;
- `Gate::allows('access-admin')`;
- при необходимости `config('admin.allowed_emails')` как временный промежуточный шаг.

### 10.2. Проверить Voyager BREAD

Проверить:

- какие модели доступны из Voyager;
- какие поля можно редактировать;
- защищены ли чувствительные поля;
- нет ли возможности редактировать баланс без аудита;
- нет ли возможности назначать себе роль администратора.

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
