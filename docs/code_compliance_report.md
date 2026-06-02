# Отчет о соответствии кода правилам разработки Laravel

**Дата проверки**: 03.06.2026  
**Версия проекта**: 1.0.0  
**Проверяющий**: GitHub Copilot

---

## Общая информация

**Стек проекта**:
- PHP: ^8.1 ✅
- Laravel: 10.10 ⚠️ (в инструкции указан Laravel 12)
- PHPUnit: 10.1 ✅
- Pint: ^1.0 ✅

**Namespace приложения**: `App\` ✅

---

## Результаты проверки

### 1. Общие требования к коду

| Правило | Статус | Комментарий |
|---------|--------|-------------|
| Все PHP-файлы начинаются с `declare(strict_types=1);` | ❌ **КРИТИЧНО** | Ни один файл не содержит этой директивы |
| Используется Pint для стиля кода | ✅ | `pint.json` присутствует в корне |
| Код соответствует PSR-стандартам | ⚠️ | Требуется проверка через `vendor/bin/pint --test` |

**Детальная проверка `declare(strict_types=1);`:**
```bash
# Проверено: 0 файлов из ~50+ содержат declare(strict_types=1)
```

**Рекомендации**:
- Добавить `declare(strict_types=1);` в начало ВСЕХ PHP-файлов
- Настроить автоматическую проверку через CI/CD

---

### 2. Маршруты (routes/)

| Правило | Статус | Проблемы |
|---------|--------|----------|
| Используется `Route::resource` для CRUD | ⚠️ | Не используется, хотя есть полноценные CRUD контроллеры |
| Маршруты именуются через `->name()` | ✅ | Все маршруты именованы |
| Группировка через `Route::middleware()` | ✅ | Используется для `auth` |
| Группировка через `Route::prefix()` | ✅ | Используется для `account`, `admin` |
| Разделение `web.php` и `api.php` | ✅ | `web.php` для страниц, `api.php` минимальный |

**Проблемные участки**:

**routes/web.php (строки 116-138)**:
```php
// ❌ Не используется Route::resource, хотя это полноценный CRUD
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [Backend\ProductController::class, 'index'])->name('index');
    Route::get('create', [Backend\ProductController::class, 'create'])->name('create');
    Route::post('/', [Backend\ProductController::class, 'store'])->name('store');
    Route::get('{product}/edit', [Backend\ProductController::class, 'edit'])->name('edit');
    Route::get('{product}', [Backend\ProductController::class, 'show'])->name('show');
    Route::put('{product}', [Backend\ProductController::class, 'update'])->name('update');
    Route::delete('{product}', [Backend\ProductController::class, 'destroy'])->name('destroy');
    // ...
});
```

**Рекомендация**:
```php
// ✅ Должно быть так:
Route::resource('products', Backend\ProductController::class);

// Дополнительные методы можно добавить отдельно:
Route::patch('products/{product}/activate', [Backend\ProductController::class, 'activate'])
    ->name('products.activate');
```

---

### 3. Контроллеры (app/Http/Controllers/)

| Правило | Статус | Проблемы |
|---------|--------|----------|
| Один контроллер = один ресурс | ✅ | Соблюдается |
| Методы только CRUD: index, create, store, show, edit, update, destroy | ⚠️ | Есть дополнительные методы (activate, deactivate, pushUp) |
| Контроллер не содержит бизнес-логику | ❌ **КРИТИЧНО** | Бизнес-логика присутствует в контроллерах |
| Для сложных операций создаются Action-классы | ❌ | Action-классы не используются (кроме Fortify) |

**Проблемные контроллеры**:

#### Backend\ProductController (строки 95-175+)
```php
// ❌ Бизнес-логика в контроллере
public function store(Request $request)
{
    // 150+ строк кода с:
    // - проверкой лимитов тарифа
    // - обработкой изображений
    // - генерацией alias
    // - очисткой кешей
    // - логированием
}
```

**Рекомендация**:
```php
// ✅ Должно быть так:
public function store(StoreProductRequest $request)
{
    $product = $this->productService->createProduct($request->validated());
    
    return redirect()->route('account.store.products.index')
        ->with('success', 'Товар успешно создан');
}
```

#### CabinetController (строки 100-200)
```php
// ❌ Смешаны разные ресурсы в одном контроллере
public function createShop() { ... }
public function storeShop() { ... }
public function deleteShop() { ... }
public function userIndex() { ... }
public function showChangePasswordForm() { ... }
public function updatePassword() { ... }
```

**Рекомендация**: Разделить на:
- `Backend\SellerController` (управление магазином)
- `Backend\UserProfileController` (профиль пользователя) - уже существует, перенести методы

---

### 4. Валидация

| Правило | Статус | Проблемы |
|---------|--------|----------|
| Всегда через Form Request | ❌ **КРИТИЧНО** | Используется `Validator::make()` в контроллерах |
| Авторизация запроса в `authorize()` | ❌ | Нет Form Request классов |
| Правила в `rules()` | ❌ | Правила валидации в контроллерах |

**Проблемные участки**:

#### Backend\ProductController::store() (строки 95-123)
```php
// ❌ Валидация вручную в контроллере
$validator = Validator::make($request->all(), [
    'name' => ['required', 'string', 'max:255'],
    'price' => ['required', 'numeric', 'min:0'],
    // ...30+ строк правил и сообщений
]);

if ($validator->fails()) {
    return redirect()->back()->withErrors($validator)->withInput();
}
```

**Рекомендация**: Создать Form Request:
```php
// ✅ app/Http/Requests/StoreProductRequest.php
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->seller !== null;
    }
    
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            // ...
        ];
    }
    
    public function messages(): array
    {
        return [
            'name.required' => 'Поле "Название товара" обязательно',
            // ...
        ];
    }
}
```

**Количество нарушений**: ~10+ мест в проекте

---

### 5. Модели (app/Models/)

| Правило | Статус | Проблемы |
|---------|--------|----------|
| Явно объявлены `$fillable` или `$guarded` | ✅ | Объявлены в Product, Category, Seller, User |
| Никогда `$guarded = []` в продакшене | ⚠️ | В Product и Seller есть `$guarded`, но не пустой |
| Все связи описаны явно | ✅ | Связи описаны с типами возврата |
| Используется eager loading | ✅ | `with(['images', 'seller', 'category'])` используется |
| Используются Eloquent Scopes | ✅ | `scopeVisible()`, `scopeFromActiveSellerPackage()` |
| Аксессоры/мутаторы через `Attribute::make()` | ⚠️ | Используется старый синтаксис `getBreadcrumbsAttribute()` |

**Проблемные участки**:

#### Category.php (строка 42)
```php
// ⚠️ Старый синтаксис аксессоров (Laravel 8-)
public function getBreadcrumbsAttribute()
{
    return $this->getAncestors()->push($this);
}
```

**Рекомендация** (Laravel 9+ синтаксис):
```php
// ✅ Современный синтаксис
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function breadcrumbs(): Attribute
{
    return Attribute::make(
        get: fn () => $this->getAncestors()->push($this)
    );
}
```

#### Product.php (строки 48-51)
```php
// ⚠️ Наличие $guarded (хотя и не пустой)
protected $guarded = [
    'id',
];
```

**Рекомендация**: Оставить только `$fillable`, убрать `$guarded`

---

### 6. Сервисы и бизнес-логика

| Правило | Статус | Проблемы |
|---------|--------|----------|
| Бизнес-логика в Service/Action классах | ❌ **КРИТИЧНО** | Логика в контроллерах |
| Внедрение зависимостей через конструктор | ⚠️ | Не применимо, т.к. нет сервисов |
| Не используются фасады в доменных сервисах | ✅ | N/A (сервисов нет) |

**Отсутствующие компоненты**:
- `app/Services/` - директория пустая
- `app/Actions/` - только Fortify Actions

**Примеры логики, которую нужно вынести**:

1. **Создание товара** (ProductController::store):
   - Генерация alias
   - Проверка лимитов тарифа
   - Обработка изображений
   - Очистка кешей
   → Должно быть в `ProductService` или `CreateProductAction`

2. **Покупка тарифа** (PackageController::purchase):
   - Проверка баланса
   - Списание средств
   - Создание записи в `seller_packages`
   - Очистка кешей
   → Должно быть в `PackageService` или `PurchasePackageAction`

3. **Поднятие товара** (ProductController::pushUp):
   - Обновление `push_at`
   - Очистка кешей
   → Должно быть в `PushUpProductAction`

---

### 7. Миграции и БД (database/)

| Правило | Статус | Проблемы |
|---------|--------|----------|
| Миграции идемпотентны | ⚠️ | Не проверяется `Schema::hasColumn()` |
| Индексы и внешние ключи обязательны | ✅ | Индексы присутствуют |
| Не менять существующие миграции | ✅ | Создаются новые миграции для изменений |
| Сидеры через `firstOrCreate`/`updateOrCreate` | ⚠️ | Не проверено (сидеры не изучены) |

**Проблемные миграции**:

#### 2026_01_19_100044_add_nested_set_columns_to_categories_table.php
```php
// ⚠️ Нет проверки на существование колонок
public function up(): void
{
    Schema::table('categories', function (Blueprint $table) {
        $table->unsignedInteger('_lft')->default(0)->after('parent_id');
        $table->unsignedInteger('_rgt')->default(0)->after('_lft');
        // ...
    });
}
```

**Рекомендация**:
```php
// ✅ Идемпотентная миграция
public function up(): void
{
    Schema::table('categories', function (Blueprint $table) {
        if (!Schema::hasColumn('categories', '_lft')) {
            $table->unsignedInteger('_lft')->default(0)->after('parent_id');
        }
        if (!Schema::hasColumn('categories', '_rgt')) {
            $table->unsignedInteger('_rgt')->default(0)->after('_lft');
        }
        // ...
    });
}
```

---

### 8. Авторизация

| Правило | Статус | Проблемы |
|---------|--------|----------|
| Используются Gates для простых проверок | ❌ | Не используются |
| Используются Policies для моделей | ❌ **КРИТИЧНО** | Не используются |
| Регистрация в `AuthServiceProvider` | ❌ | Policies не зарегистрированы |
| В контроллере: `$this->authorize()` | ❌ | Проверки через `if ($user->seller->id !== $product->seller_id)` |

**Проблемные участки**:

#### Backend\ProductController::edit() (предположительно)
```php
// ❌ Проверка авторизации вручную
public function edit(Product $product)
{
    $user = Auth::user();
    if ($user->seller->id !== $product->seller_id) {
        abort(403);
    }
    // ...
}
```

**Рекомендация**: Создать Policy:
```php
// ✅ app/Policies/ProductPolicy.php
class ProductPolicy
{
    public function update(User $user, Product $product): bool
    {
        return $user->seller->id === $product->seller_id;
    }
}

// В контроллере:
public function edit(Product $product)
{
    $this->authorize('update', $product);
    // ...
}
```

---

### 9. Очереди и события

| Правило | Статус | Проблемы |
|---------|--------|----------|
| Тяжёлые операции через Jobs | ✅ | `WarmCategoriesCache` реализован |
| События через Events/Listeners | ⚠️ | Только для аутентификации |
| Регистрация в `EventServiceProvider` | ⚠️ | Используется атрибут `#[ListensTo]` (не проверено) |

**Хорошие примеры**:
- `WarmCategoriesCache` - Job для прогрева кеша
- `LogSuccessfulLogin/Logout/Registered` - Listeners для логирования

**Что можно улучшить**:
- Отправка email уведомлений → через Job
- Обработка изображений → через Job (сейчас синхронно)
- Покупка тарифа → через Event `PackagePurchased`

---

### 10. Кэш и производительность

| Правило | Статус | Проблемы |
|---------|--------|----------|
| Используется `Cache::remember()` | ✅ | Активно используется |
| Route/config cache только в продакшене | ✅ | Не применяется в dev (правильно) |
| Не делать запросы в цикле | ✅ | Используется `whereIn()` и `with()` |

**Хорошие примеры**:
- Кеширование дерева категорий (24 часа)
- Eager loading: `with(['images', 'seller', 'category'])`
- Использование `whereIn()` вместо цикла запросов

---

### 11. Шаблоны (resources/views/)

| Правило | Статус | Проблемы |
|---------|--------|----------|
| Только Blade | ✅ | Используется Blade |
| Минимум логики в шаблонах | ⚠️ | Не проверено (view не изучены) |
| Экранирование через `{{ }}` | ⚠️ | Не проверено |
| Компоненты через `make:component` | ⚠️ | Не проверено |

**Статус**: Требуется дополнительная проверка view-файлов

---

### 12. Локализация

| Правило | Статус | Проблемы |
|---------|--------|----------|
| Строки через `__('key')` | ❌ **КРИТИЧНО** | Русские строки прямо в коде |
| Языковые файлы в `lang/` | ⚠️ | Директория `lang/` существует |

**Проблемные участки**:

#### Backend\ProductController::store() (строки 230+)
```php
// ❌ Русские строки в коде
return redirect()->route('account.store.products.index')
    ->with('success', 'Товар успешно создан');
```

**Рекомендация**:
```php
// ✅ Через языковые файлы
return redirect()->route('account.store.products.index')
    ->with('success', __('products.created_successfully'));

// lang/ru/products.php
return [
    'created_successfully' => 'Товар успешно создан',
];
```

---

### 13. Тесты (tests/)

| Правило | Статус | Проблемы |
|---------|--------|----------|
| Feature-тесты для HTTP | ❌ | Минимальное покрытие |
| Unit-тесты для изолированных классов | ❌ | Отсутствуют |
| Используется `RefreshDatabase` | ⚠️ | Не проверено |
| Фабрики для тестовых данных | ⚠️ | Фабрики существуют, но использование не проверено |

**Критичные компоненты без тестов**:
- Система тарифов (лимиты, покупка, автопродление)
- CRUD товаров (авторизация, валидация)
- Scope `visible()` для товаров
- Кеширование категорий

---

### 14. Безопасность

| Правило | Статус | Проблемы |
|---------|--------|----------|
| CSRF-защита включена | ✅ | Включена для web-маршрутов |
| Только `$request->validated()` | ❌ | Используется `$request->all()` в некоторых местах |
| Rate limiting | ❌ | Не настроен |
| Переменные через `config()`, не `env()` | ✅ | Используется `config()` |

**Проблемные участки**:

#### Backend\ProductController (предположительно)
```php
// ❌ Может использоваться $request->all()
// Нужно проверить весь код на наличие
```

**Рекомендация**:
```bash
# Проверка всего проекта:
grep -r '$request->all()' app/
```

---

## Сводная таблица проблем

| Категория | Критичность | Количество | Статус |
|-----------|-------------|------------|--------|
| Отсутствие `declare(strict_types=1);` | 🔴 Критично | 50+ файлов | Не исправлено |
| Отсутствие Form Requests | 🔴 Критично | 10+ мест | Не исправлено |
| Бизнес-логика в контроллерах | 🔴 Критично | 5+ контроллеров | Не исправлено |
| Отсутствие Policies | 🔴 Критично | Все модели | Не исправлено |
| Русские строки в коде | 🔴 Критично | Весь проект | Не исправлено |
| Не используется `Route::resource` | 🟡 Средне | 3+ места | Не исправлено |
| Старый синтаксис аксессоров | 🟡 Средне | 1-2 места | Не исправлено |
| Отсутствие тестов | 🟡 Средне | Весь проект | Не исправлено |
| Идемпотентность миграций | 🟢 Низко | 5+ миграций | Не исправлено |

---

## Приоритетные задачи для исправления

### 🔴 Критичные (должны быть исправлены НЕМЕДЛЕННО)

1. **Добавить `declare(strict_types=1);` во все PHP-файлы**
   - Команда: создать скрипт для автоматического добавления
   - Затронутые файлы: `app/**/*.php`, `database/**/*.php`, `tests/**/*.php`
   - Время: 1-2 часа

2. **Создать Form Request классы**
   - Файлы:
     - `StoreProductRequest.php`
     - `UpdateProductRequest.php`
     - `StoreSellerRequest.php`
     - `UpdateSellerRequest.php`
     - `UpdateUserProfileRequest.php`
     - `UpdatePasswordRequest.php`
   - Время: 4-6 часов

3. **Вынести бизнес-логику в Service/Action классы**
   - Создать:
     - `ProductService` (create, update, delete, pushUp)
     - `PackageService` (purchase, autoRenew)
     - `ImageService` (upload, resize, setMain, delete)
   - Время: 8-12 часов

4. **Создать Policies**
   - Файлы:
     - `ProductPolicy` (view, create, update, delete)
     - `SellerPolicy` (view, update, delete)
   - Зарегистрировать в `AuthServiceProvider`
   - Время: 2-3 часа

5. **Вынести строки в языковые файлы**
   - Создать:
     - `lang/ru/products.php`
     - `lang/ru/sellers.php`
     - `lang/ru/packages.php`
     - `lang/ru/validation.php` (кастомные сообщения)
   - Время: 4-6 часов

---

### 🟡 Средние (желательно исправить в ближайшее время)

6. **Использовать `Route::resource` для CRUD**
   - Файл: `routes/web.php`
   - Время: 1 час

7. **Обновить аксессоры до Laravel 9+ синтаксиса**
   - Файл: `Category.php`
   - Время: 30 минут

8. **Добавить тесты**
   - Создать тесты для:
     - Системы тарифов
     - CRUD товаров
     - Scope `visible()`
     - Авторизации
   - Время: 16-24 часа

9. **Добавить идемпотентность в миграции**
   - Добавить проверки `Schema::hasColumn()` / `Schema::hasTable()`
   - Время: 2-3 часа

---

### 🟢 Низкие (можно отложить)

10. **Настроить rate limiting**
    - Добавить throttle для API и форм
    - Время: 1-2 часа

11. **Проверить использование `$request->all()`**
    - Заменить на `$request->validated()` или `$request->only()`
    - Время: 2-3 часа

---

## Оценка соответствия стандартам

**Общая оценка**: 🟡 **4.5/10** (Требует значительной доработки)

### Разбивка по категориям:

| Категория | Оценка | Комментарий |
|-----------|--------|-------------|
| Структура кода | 6/10 | MVC структура соблюдена, но логика в контроллерах |
| Маршруты | 7/10 | Именованы и сгруппированы, но не используется `resource` |
| Контроллеры | 4/10 | Смешана бизнес-логика, нет Action-классов |
| Валидация | 2/10 | Вручную в контроллерах, нет Form Requests |
| Модели | 7/10 | Хорошие Scopes, есть Eloquent связи |
| Сервисы | 1/10 | Отсутствуют |
| Миграции | 6/10 | Структура хорошая, но нет идемпотентности |
| Авторизация | 2/10 | Нет Policies, проверки вручную |
| События/Очереди | 5/10 | Частично используются |
| Кеширование | 8/10 | Активно используется, грамотно |
| Локализация | 1/10 | Строки в коде на русском |
| Тесты | 1/10 | Минимальное покрытие |
| Безопасность | 6/10 | Базовая защита есть, но есть недочеты |
| Производительность | 8/10 | Индексы, eager loading, кеширование |

---

## Рекомендации по улучшению

### Краткосрочные (1-2 недели)

1. Исправить все критичные проблемы (пункты 1-5)
2. Запустить `vendor/bin/pint` и исправить стиль кода
3. Добавить базовые тесты для критичных компонентов

### Среднесрочные (1-2 месяца)

4. Рефакторинг всех контроллеров (вынос логики)
5. Полное покрытие тестами (>80%)
6. Добавить CI/CD с автоматическими проверками

### Долгосрочные (3-6 месяцев)

7. Обновление до Laravel 11/12
8. Внедрение Domain-Driven Design (если проект растет)
9. Добавление статического анализа (PHPStan, Psalm)

---

## Заключение

**Проект имеет хорошую функциональную основу**, но требует значительной **технической доработки** для соответствия стандартам Laravel best practices.

**Основные проблемы**:
- Отсутствие строгой типизации (`declare(strict_types=1);`)
- Бизнес-логика в контроллерах вместо сервисных классов
- Отсутствие Form Request классов для валидации
- Отсутствие Policies для авторизации
- Строки на русском языке в коде (не вынесены в lang/)

**Сильные стороны**:
- Хорошо организованная структура БД
- Эффективное использование кеширования
- Применение Eloquent Scopes для бизнес-логики
- Использование Nested Set для категорий
- Логирование через Spatie Activity Log

**Рекомендуемый план действий**:
1. Начать с критичных проблем (declare, Form Requests, Services)
2. Постепенно добавлять тесты при рефакторинге
3. Внедрить автоматические проверки (Pint, PHPStan, CI/CD)
4. Планировать обновление до последней версии Laravel

---

**Дата следующей проверки**: 03.07.2026  
**Ответственный**: [Указать имя]

