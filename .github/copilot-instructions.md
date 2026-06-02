# Copilot: инструкция по разработке на Laravel

## Стек
- **PHP 8.2+**, **Laravel 12**, **PHPUnit 11**, **Pint** (code style)
- Namespace приложения: `App\`; тесты: `Tests\`
- Все PHP-файлы начинаются с `declare(strict_types=1);`
- Стиль кода — Laravel Pint (pint.json в корне)

## Структура кода — «чистый Laravel», без костылей

### Маршруты (`routes/`)
- Используй `Route::resource` / `Route::apiResource` для CRUD.
- Именуй маршруты через `->name()`; не хардкодь URL в коде.
- Группируй через `Route::middleware()` и `Route::prefix()`.
- `web.php` — для страниц с сессией; `api.php` — для JSON API.

### Контроллеры (`app/Http/Controllers/`)
- Один контроллер = один ресурс; методы только: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.
- Контроллер не содержит бизнес-логику — только вызов сервиса/экшена и возврат ответа.
- Для сложных операций создавай Action-классы в `app/Actions/`.

### Валидация
- Всегда через **Form Request** (`php artisan make:request`); правила — в методе `rules()`.
- Авторизация запроса — в методе `authorize()` Form Request, не в контроллере.
- Никогда не валидируй вручную через `$request->validate()` в контроллере, если есть Form Request.

### Модели (`app/Models/`)
- Явно объявляй `$fillable` или `$guarded`; никогда не оставляй `$guarded = []` в продакшене.
- Все связи описывай явно (`hasMany`, `belongsTo`, `belongsToMany` и т.д.) с указанием типа.
- Избегай N+1: всегда используй eager loading (`with()`, `load()`).
- Используй **Eloquent Scopes** для повторяющихся условий выборки.
- Аксессоры и мутаторы — через `Attribute::make()` (Laravel 9+ синтаксис).

### Сервисы и бизнес-логика
- Бизнес-логика — в сервисных классах (`app/Services/`) или Action-классах (`app/Actions/`).
- Внедряй зависимости через конструктор (DI); регистрируй биндинги в `AppServiceProvider`.
- Не используй фасады внутри доменных сервисов — принимай интерфейсы/контракты.

### Миграции и БД (`database/`)
- Каждая миграция идемпотентна: `up()` проверяет существование через `Schema::hasColumn()` при необходимости.
- Индексы и внешние ключи — обязательно.
- Никогда не меняй существующие миграции, которые уже были применены — создавай новую.
- Сидеры (`database/seeders/`) через `firstOrCreate`/`updateOrCreate` — не `create()`.

### Авторизация
- Используй **Gates** для простых проверок, **Policies** — для моделей.
- Регистрируй политики в `AuthServiceProvider` (или через `#[Policy]` в Laravel 12).
- В контроллере: `$this->authorize()` или `Gate::authorize()`; в Blade: `@can`/`@cannot`.

### Очереди и события
- Тяжёлые операции (email, обработка файлов) — только через Jobs (`php artisan make:job`).
- Бизнес-события — через Events/Listeners (`php artisan make:event`, `make:listener`).
- Слушатели регистрируй в `EventServiceProvider` или через атрибут `#[ListensTo]`.

### Кэш и производительность
- Используй `Cache::remember()` для дорогих запросов.
- Route cache (`php artisan route:cache`) и config cache (`php artisan config:cache`) — только в продакшене.
- Не делай запросы в цикле — заменяй на `whereIn` или `with()`.

### Шаблоны (`resources/views/`)
- Только **Blade**; логики в шаблонах — минимум.
- Экранируй вывод через `{{ }}` (не `{!! !!}`), кроме случаев с заведомо безопасным HTML.
- Компоненты — через `php artisan make:component`; анонимные компоненты в `resources/views/components/`.

### Локализация
- Все пользовательские строки — через `__('key')` или `@lang('key')`.
- Языковые файлы — в `lang/ru/` и `lang/en/`.
- Никаких строк на русском/английском языке прямо в PHP-логике или Blade.

### Тесты (`tests/`)
- **Feature-тесты** — проверяют HTTP-эндпоинты через `$this->get()`, `$this->post()` и т.д.
- **Unit-тесты** — проверяют изолированные сервисы/классы без БД (используй моки).
- Используй `RefreshDatabase` или `LazilyRefreshDatabase` в Feature-тестах.
- Фабрики (`database/factories/`) для генерации тестовых данных — не хардкодь данные.
- Запуск: `php artisan test`

### Безопасность
- CSRF-защита включена по умолчанию для web-маршрутов — не отключай без причины.
- Никогда не передавай `$request->all()` напрямую в модель — только `$request->validated()`.
- Rate limiting — через `RateLimiter::for()` в `AppServiceProvider` или через middleware `throttle:`.
- Переменные окружения — только через `.env` и `config()`; никогда `env()` в коде вне `config/`.

## Запуск проверок
```bash
php artisan test                          # тесты
vendor/bin/pint --test                    # проверка стиля (без изменений)
vendor/bin/pint                           # автоисправление стиля
```
