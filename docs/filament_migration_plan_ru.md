# План переноса управления сайтом с Voyager на Filament

Дата актуализации: 07.06.2026

## Статус выполнения

На 07.06.2026 выполнена техническая часть этапов 0–1:

- baseline до установки: 279 тестов, 664 assertions, 165 маршрутов;
- `composer validate` проходит с предупреждениями по ограничениям Voyager и Nested Set;
- frontend baseline не собран: команда `npm` отсутствует в текущем `PATH`;
- Filament `3.3.52` установлен параллельно с Voyager;
- панель `control` зарегистрирована на `/control`, Voyager сохранён на `/admin`;
- доступ использует guard `web`, контракт `FilamentUser` и Gate `access-admin`;
- `FILAMENT_PANEL_ENABLED` по умолчанию выключен и включает панель только явно;
- добавлены feature-тесты доступа к панели: 4 теста, 6 assertions;
- итоговая проверка после установки: 283 теста, 670 assertions, 175 маршрутов;
- `composer audit` сокращён с 10 до 5 advisories обновлением PHPUnit, PsySH и Symfony YAML;
- оставшиеся advisories относятся к Laravel 10 и Voyager и устраняются только следующими этапами миграции;
- резервные копии БД и `storage/app/public` необходимо выполнить перед переносом данных и удалением Voyager.

## 1. Цель

Заменить Voyager на Filament без остановки сайта и без изменения бизнес-логики публичной витрины и кабинета продавца.

После завершения:

- административная панель работает на Filament;
- администратор управляет пользователями, продавцами, товарами, категориями, тарифами и подписками;
- журнал действий и дерево категорий перенесены;
- доступ к панели использует Gate `access-admin`;
- операции с товарами, тарифами, изображениями, балансом и кешем проходят через существующие сервисы;
- Voyager полностью удалён из runtime-кода и Composer;
- полный набор тестов проходит.

## 2. Фактическое состояние проекта

### 2.1. Технологии

- Laravel 10.50.2;
- PHP в `composer.json`: `^8.1`, локально используется PHP 8.4;
- Voyager 1.7;
- Filament 3.3.52;
- Fortify;
- Spatie Activitylog;
- Kalnoy Nested Set;
- PHPUnit 10;
- текущая проверка: 279 тестов, 664 assertions;
- зарегистрировано 165 маршрутов.

### 2.2. Текущая админка

Voyager подключён на `/admin`.

Доступ:

- middleware `admin.access`;
- Gate `access-admin`;
- permission Voyager `browse_admin`.

Фактические Voyager BREAD в текущей БД:

- `users`;
- `roles`;
- `menus`.

Кастомные страницы:

- журнал действий: `admin.activity-log`;
- дерево категорий: `admin.category-tree`.

Voyager Settings и Media Manager напрямую приложением не используются. Изображения товаров и продавцов обслуживаются собственными сервисами и диском `public`.

### 2.3. Доменные модели для новой панели

- `User`;
- `Seller`;
- `Product`;
- `Category`;
- `Package`;
- `SellerPackage`;
- `Image` — преимущественно как relation manager товара;
- `Activity` — только чтение.

## 3. Решение по версии Filament

### 3.1. Ограничение

Filament 5 требует:

- PHP 8.2+;
- Laravel 11.28+.

Voyager 1.7 поддерживает только Laravel 8–10 и блокирует обновление проекта до Laravel 11.

### 3.2. Рекомендуемый путь

Для безопасного параллельного запуска использовать:

```bash
composer require filament/filament:"^3.3" -W
php artisan filament:install --panels
```

Filament 3 поддерживает Laravel 10 и PHP 8.1+. Security fixes заявлены до января 2028 года.

После полного удаления Voyager выполнить отдельный технический проект:

1. обновить Laravel до 11.28+;
2. обновить Filament 3 → 4;
3. обновить Filament 4 → 5;
4. повторно выполнить полный набор тестов.

Нельзя пытаться установить Filament 5 прямо в текущий проект: это одновременно потребует Laravel 11 и удаления Voyager, лишая проект безопасного параллельного режима.

## 4. Основные принципы миграции

1. Voyager и Filament временно работают параллельно.
2. Filament сначала запускается на отдельном пути `/control`.
3. Доменные таблицы не копируются и не переносятся: Filament работает с текущими Eloquent-моделями.
4. Filament Resources не должны дублировать бизнес-логику.
5. Критические операции вызывают существующие сервисы.
6. Баланс нельзя редактировать обычным `TextInput`.
7. Роли нельзя назначать через массовое обновление пользователя.
8. Voyager удаляется только после функционального паритета и тестов.
9. Каждый ресурс переносится отдельным проверяемым этапом.

## 5. Целевая архитектура

```text
App\Providers\Filament\AdminPanelProvider
    |
    +-- Gate access-admin
    |
    +-- Resources
    |   +-- UserResource
    |   +-- SellerResource
    |   +-- ProductResource
    |   +-- CategoryResource
    |   +-- PackageResource
    |   +-- SellerPackageResource
    |
    +-- Pages
    |   +-- Dashboard
    |   +-- ActivityLog
    |   +-- CategoryTree
    |
    +-- Widgets
        +-- ProductsStats
        +-- SellersStats
        +-- PackagesStats
```

Filament вызывает:

- Laravel Policies для доступа;
- `ProductWriteService` для создания и изменения товаров;
- `ProductActionService` для публикации, снятия, поднятия и удаления;
- `ProductImageService` для изображений;
- `ProductLimitService` для тарифных ограничений;
- `PackageService` для покупки и продления;
- `CatalogCacheService` и observers для инвалидации кеша;
- отдельные административные сервисы для операций, которых сейчас нет.

## 6. Этапы выполнения

## Этап 0. Зафиксировать baseline

Перед установкой:

```bash
php artisan test
php artisan route:list
composer validate
npm run build
```

Зафиксировать:

- список Voyager-маршрутов;
- список BREAD;
- список ролей и permissions;
- список пунктов Voyager-меню;
- резервную копию БД;
- резервную копию `storage/app/public`.

Критерий завершения:

- baseline записан в DOCS;
- тесты проходят;
- восстановление БД проверено.

## Этап 1. Установить Filament в параллельном режиме

Действия:

1. Установить Filament 3.3.
2. Выполнить `php artisan filament:install --panels`.
3. Зарегистрировать `ControlPanelProvider` в `config/app.php`.
4. Установить panel id `control`.
5. Установить panel path `/control`.
6. Использовать guard `web`.
7. Не включать отдельную регистрацию пользователей Filament.
8. Настроить русский язык и часовой пояс приложения.
9. Добавить env-флаг:

```env
FILAMENT_PANEL_ENABLED=false
```

На production и в нужном локальном окружении панель включается явным значением `true`.

Доступ:

- `User` реализует `FilamentUser`;
- `canAccessPanel()` вызывает Gate `access-admin`;
- пользователи без `browse_admin` получают 403;
- Voyager продолжает работать на `/admin`.

Тесты:

- администратор открывает `/control`;
- обычный пользователь получает 403;
- гость перенаправляется на login;
- отключённый env-флаг скрывает панель.

Критерий завершения:

- пустая Filament-панель работает;
- Voyager не изменён;
- полный набор тестов проходит.

## Этап 2. Подготовить авторизацию без зависимости от Voyager

Этот этап выполняется до удаления Voyager, но после запуска Filament.

Действия:

1. Создать локальные модели:
   - `App\Models\Role`;
   - `App\Models\Permission`.
2. Перенести в `User` связи `role()`, `roles()`, `rolesAll()`.
3. Реализовать локальный `hasPermission()`.
4. Сохранить существующие таблицы:
   - `roles`;
   - `permissions`;
   - `permission_role`;
   - `user_roles`.
5. Добавить application-owned migrations для этих таблиц, чтобы fresh install не зависел от миграций Voyager.
6. Перевести Gate `access-admin` на локальную реализацию.
7. Заменить наследование `TCG\Voyager\Models\User` на стандартный Laravel `Authenticatable`.

Важно:

- данные ролей не копируются;
- идентификаторы ролей и пользователей не меняются;
- Gate продолжает работать до и после удаления Voyager.

Тесты:

- permission получается из основной роли;
- permission получается из дополнительной роли;
- пользователь без permission не проходит Gate;
- существующие администраторы сохраняют доступ.

## Этап 3. Добавить политики административных ресурсов

Текущие `ProductPolicy` и `SellerPolicy` ориентированы на владельца магазина. Filament нужен административный доступ.

Действия:

1. Добавить административный `before()` или отдельные abilities.
2. Администратору с `access-admin` разрешить операции панели.
3. Сохранить ограничения владельца для кабинета продавца.
4. Создать policies:
   - `UserPolicy`;
   - `CategoryPolicy`;
   - `PackagePolicy`;
   - `SellerPackagePolicy`.
5. Реализовать методы:
   - `viewAny`;
   - `view`;
   - `create`;
   - `update`;
   - `delete`;
   - `deleteAny`;
   - `reorder`, где требуется.

Защитные правила:

- нельзя удалить собственного администратора;
- нельзя снять последнюю административную роль;
- bulk delete пользователей выключен;
- bulk delete категорий и тарифов выключен;
- баланс не входит в обычный update пользователя.

## Этап 4. Перенести пользователей

Создать `UserResource`.

Таблица:

- имя;
- email;
- телефон;
- баланс только для чтения;
- основная роль;
- наличие магазина;
- дата регистрации;
- фильтр по роли;
- поиск по имени/email.

Форма:

- имя;
- email;
- телефон;
- без поля `balance`;
- без прямого поля `role_id`;
- без редактирования пароля существующего пользователя.

Отдельные действия:

- отправить reset password;
- назначить/снять роль через подтверждаемое действие;
- заблокировать опасные действия над собственным аккаунтом.

Баланс:

- создать `BalanceAdjustmentService`;
- действие принимает сумму и обязательную причину;
- операция выполняется в транзакции с `lockForUpdate()`;
- создаётся activity log с прежним и новым балансом;
- отрицательный итоговый баланс запрещён.

Тесты:

- список открывается;
- пользователь редактируется без изменения роли и баланса;
- корректировка баланса атомарна;
- причина обязательна;
- операция логируется;
- обычный пользователь не видит ресурс.

## Этап 5. Перенести продавцов

Создать `SellerResource`.

Таблица:

- название;
- владелец;
- alias;
- город;
- активные товары;
- активный тариф;
- автопродление;
- дата создания.

Форма:

- профиль магазина;
- контакты;
- социальные ссылки;
- логотип через существующий `SellerProfileService`;
- `user_id` нельзя незаметно менять после создания;
- `product_count` недоступен для ручного ввода.

Relation managers:

- товары;
- тарифная история.

Действия:

- открыть публичную страницу магазина;
- перейти к товарам;
- включить/выключить автопродление через `SellerSettingsService`;
- удалить магазин через отдельный подтверждаемый административный сервис.

## Этап 6. Перенести товары

Создать `ProductResource`.

Это наиболее рискованный ресурс. Запрещено использовать обычный `record->update()` для сложных операций.

Таблица:

- название;
- продавец;
- категория;
- цена и цена скидки;
- active/VIP;
- количество изображений;
- дата поднятия;
- фильтры по продавцу, категории, active, VIP, скидке;
- поиск по названию и alias.

Создание/редактирование:

- использовать правила `ProductRequest`;
- использовать `ProductWriteService`;
- категории выбирать только конечные;
- лимиты проверять через `ProductLimitService`;
- изображения передавать в `ProductImageService`;
- скидочные даты не рассчитывать внутри Resource.

Отдельные actions:

- активировать;
- деактивировать;
- поднять;
- назначить главное изображение;
- удалить изображение;
- удалить товар.

Actions вызывают `ProductActionService`.

Relation manager:

- `ImagesRelationManager`;
- preview;
- загрузка;
- главное изображение;
- сортировка, если она поддерживается моделью.

Тесты:

- CRUD;
- запрет не конечной категории;
- запрет активного товара без изображения;
- активный/VIP лимиты;
- действия публикации;
- удаление файлов;
- инвалидация кеша.

## Этап 7. Перенести категории

Создать `CategoryResource`.

Форма:

- название;
- alias;
- номер сортировки;
- описание;
- иконка и цвета;
- родитель;
- active.

Ограничения:

- категория не может быть родителем самой себе;
- категория не может перемещаться внутрь собственного потомка;
- удаление категории с товарами запрещается либо требует отдельного сценария переноса;
- изменение дерева использует методы Nested Set;
- после изменения вызывается существующий observer и прогрев кеша.

Создать Filament Page `CategoryTree`.

Функции:

- дерево всех категорий;
- expand/collapse;
- быстрый переход к редактированию;
- количество товаров;
- статус активности;
- безопасный reorder отдельным этапом.

Не начинать с drag-and-drop. Сначала реализовать read-only дерево и обычное изменение родителя через форму.

## Этап 8. Перенести тарифы и подписки

### `PackageResource`

Поля:

- название;
- цена;
- длительность;
- лимит активных товаров;
- лимит VIP;
- возможности;
- active.

Ограничения:

- нельзя удалить тариф, если есть история подписок;
- изменение лимитов не должно автоматически деактивировать товары без отдельного решения;
- нулевой лимит и unlimited должны иметь однозначное представление.

### `SellerPackageResource`

Таблица:

- продавец;
- тариф;
- даты;
- active.

Операции:

- просмотр истории;
- ручное назначение тарифа;
- продление;
- деактивация.

Для ручного назначения создать `AdminPackageAssignmentService`:

- транзакция;
- отключение предыдущего активного тарифа;
- выбор: списывать баланс или назначать бесплатно;
- обязательная причина;
- activity log.

Нельзя напрямую редактировать `seller_packages` через стандартную форму без сервиса.

## Этап 9. Перенести журнал действий

Создать Filament Page `ActivityLog`.

Сохранить:

- фильтр `log_name`;
- поиск по description;
- поиск по названию продавца;
- фильтр по инициатору;
- пагинацию;
- просмотр properties;
- дату и IP/metadata, если они записаны.

Страница только для чтения.

Старый `ActivityLogController` и Voyager Blade пока не удаляются.

## Этап 10. Добавить Dashboard

Widgets:

- количество пользователей;
- количество продавцов;
- активные/неактивные товары;
- VIP товары;
- активные тарифы;
- истекающие тарифы;
- последние действия.

Требования:

- агрегаты вынести в `AdminDashboardService`;
- не выполнять N+1;
- тяжёлые агрегаты кешировать;
- ссылки виджетов ведут в соответствующие Resources.

## Этап 11. Проверить функциональный паритет

Создать таблицу сравнения:

| Функция | Voyager | Filament | Проверено |
|---|---:|---:|---:|
| Вход администратора | Да | Да | |
| Пользователи | Да | Да | |
| Роли | Да | Да/ограниченно | |
| Продавцы | Нет BREAD | Да | |
| Товары | Нет BREAD | Да | |
| Категории | Custom page | Да | |
| Тарифы | Нет BREAD | Да | |
| Подписки | Нет BREAD | Да | |
| Журнал действий | Custom page | Да | |
| Media Manager | Да | Не требуется | |
| Settings | Да | Не используется | |

Проверить вручную:

- формы;
- фильтры;
- upload/delete файлов;
- права;
- activity log;
- кеш;
- мобильную ширину панели;
- русские подписи;
- обработку validation errors.

## Этап 12. Переключить маршруты

Перед переключением:

1. полный backup БД;
2. полный backup storage;
3. полный PHPUnit;
4. smoke-тест `/control`;
5. проверка администратора в production-like окружении.

Переключение:

1. временно отключить старую группу Voyager;
2. изменить path Filament с `/control` на `/admin`;
3. очистить route/config/view cache;
4. выполнить smoke-тест;
5. наблюдать ошибки и логи.

Откат:

1. вернуть Filament на `/control`;
2. включить Voyager `/admin`;
3. очистить кеш маршрутов;
4. данные не откатывать, так как обе панели используют одни таблицы.

## Этап 13. Удалить Voyager

Удалять только после стабильной работы Filament.

Код:

- `Voyager::routes()`;
- `config/voyager.php`;
- старые admin controllers;
- старые Voyager Blade;
- `VoyagerDatabaseSeeder` и зависимые сидеры;
- Voyager menu/settings/BREAD код;
- `tcg/voyager` из `composer.json`.

Модель пользователя:

- не должна наследоваться от Voyager;
- роли и permissions принадлежат приложению;
- Gate `access-admin` продолжает работать.

База:

- сохранить таблицы ролей/permissions как application-owned;
- удалить `data_types`, `data_rows`, `menus`, `menu_items`, `settings` только отдельной миграцией;
- перед удалением экспортировать содержимое этих таблиц;
- не удалять таблицы одновременно с Composer-пакетом.

После удаления:

```bash
composer update
php artisan optimize:clear
php artisan migrate
php artisan test
npm run build
```

## Этап 14. Обновить Laravel и Filament

Отдельная задача после удаления Voyager:

1. обновить Laravel 10 → Laravel 11.28+;
2. обновить Sanctum, Collision и связанные пакеты;
3. выполнить upgrade Filament 3 → 4;
4. выполнить upgrade Filament 4 → 5;
5. проверить Tailwind/Vite;
6. выполнить полный набор тестов после каждого major upgrade.

Не объединять удаление Voyager и два major upgrade Filament в один этап.

## 7. Стратегия тестирования

Для каждого Resource:

1. route/render test;
2. access test;
3. list records test;
4. search/filter test;
5. create test;
6. edit test;
7. validation test;
8. delete/action test;
9. service integration test.

Обязательные общие тесты:

- `FilamentPanelAccessTest`;
- `UserResourceTest`;
- `SellerResourceTest`;
- `ProductResourceTest`;
- `CategoryResourceTest`;
- `PackageResourceTest`;
- `SellerPackageResourceTest`;
- `ActivityLogPageTest`;
- `CategoryTreePageTest`;
- `BalanceAdjustmentServiceTest`;
- `AdminPackageAssignmentServiceTest`;
- `VoyagerRemovalBootTest`.

После каждого этапа:

```bash
php artisan test <целевые тесты>
php artisan test
php artisan route:list
php artisan view:cache
php artisan view:clear
```

## 8. Разделение на небольшие изменения

Рекомендуемый порядок pull requests:

1. Filament 3.3 + пустая панель + доступ.
2. Локальные Role/Permission + отвязка User от Voyager.
3. Policies административных ресурсов.
4. UserResource + безопасная корректировка баланса.
5. SellerResource.
6. ProductResource + ImagesRelationManager.
7. CategoryResource + read-only CategoryTree.
8. PackageResource + SellerPackageResource.
9. ActivityLog + Dashboard.
10. Функциональный паритет и smoke-тесты.
11. Переключение `/control` → `/admin`.
12. Удаление Voyager.
13. Laravel/Filament major upgrades отдельным проектом.

## 9. Критерии готовности

Миграция считается завершённой, если:

- `/admin` обслуживается Filament;
- Voyager routes отсутствуют;
- `tcg/voyager` отсутствует в Composer;
- `User` не зависит от Voyager;
- администратор управляет всеми необходимыми доменными сущностями;
- баланс меняется только специальным сервисом с аудитом;
- роли нельзя назначить через массовое редактирование пользователя;
- товарные действия используют существующие сервисы;
- дерево категорий корректно сохраняет Nested Set;
- журнал действий доступен только для чтения;
- fresh install создаёт роли и permissions без Voyager;
- полный набор тестов проходит;
- production build frontend проходит;
- проверен сценарий отката.

## 10. Что не делать

- не устанавливать Filament 5 в текущий Laravel 10;
- не обновлять Laravel до удаления Voyager;
- не удалять Voyager в начале;
- не копировать доменные данные в новые таблицы;
- не редактировать баланс обычным полем формы;
- не обходить `ProductWriteService`, `ProductActionService`, `PackageService`;
- не давать Filament Resources `shouldSkipAuthorization`;
- не включать bulk delete для пользователей, категорий и тарифов;
- не добавлять drag-and-drop дерева до стабильного CRUD;
- не объединять миграцию панели и крупный frontend-рефакторинг.

## 11. Источники

- Filament 3 installation: https://filamentphp.com/docs/3.x/panels/installation
- Filament 3 panel users: https://filamentphp.com/docs/3.x/panels/users
- Filament 3 resources and policies: https://filamentphp.com/docs/3.x/panels/resources/getting-started
- Filament 3 testing: https://filamentphp.com/docs/3.x/panels/testing
- Filament support policy: https://filamentphp.com/docs/5.x/introduction/version-support-policy
- Filament 5 requirements: https://filamentphp.com/docs/5.x/introduction/installation
