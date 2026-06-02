# Чек-лист разработки Laravel (по инструкции проекта)

## ✅ Перед созданием нового файла

- [ ] Добавить `declare(strict_types=1);` в начало файла (после `<?php`)
- [ ] Указать namespace согласно структуре `App\`
- [ ] Добавить use-импорты для используемых классов

## ✅ Маршруты (routes/)

### При создании нового маршрута:
- [ ] Использовать `Route::resource()` для CRUD (index, create, store, show, edit, update, destroy)
- [ ] Именовать все маршруты через `->name('resource.action')`
- [ ] Группировать через `Route::middleware()` и `Route::prefix()`
- [ ] Не хардкодить URL в коде - использовать `route('name')`

### Примеры:
```php
// ✅ Правильно
Route::resource('products', ProductController::class);
Route::middleware('auth')->group(function () {
    Route::prefix('account')->name('account.')->group(function () {
        // ...
    });
});

// ❌ Неправильно
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/create', [ProductController::class, 'create']);
// ... и т.д.
```

## ✅ Контроллеры (app/Http/Controllers/)

### Структура контроллера:
- [ ] Один контроллер = один ресурс
- [ ] Методы только CRUD: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- [ ] НЕ содержит бизнес-логику - только вызов сервиса и возврат ответа
- [ ] Использует Form Request для валидации (не `$request->validate()`)

### Примеры:
```php
// ✅ Правильно
class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}
    
    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->createProduct($request->validated());
        
        return redirect()->route('products.index')
            ->with('success', __('products.created'));
    }
}

// ❌ Неправильно
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [...]); // Валидация в контроллере
    
    // 100+ строк бизнес-логики в контроллере
    $product = new Product();
    $product->name = $request->name;
    $product->alias = Str::slug($request->name);
    // ... обработка изображений, кеширование, логирование
}
```

## ✅ Валидация

### Всегда через Form Request:
- [ ] Создать класс через `php artisan make:request StoreProductRequest`
- [ ] Правила валидации в методе `rules()`
- [ ] Авторизация в методе `authorize()`
- [ ] Кастомные сообщения в методе `messages()`
- [ ] В контроллере принимать Request как параметр

### Примеры:
```php
// ✅ Правильно - app/Http/Requests/StoreProductRequest.php
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
        ];
    }
    
    public function messages(): array
    {
        return [
            'name.required' => __('validation.product.name.required'),
        ];
    }
}

// В контроллере:
public function store(StoreProductRequest $request)
{
    // $request->validated() уже проверено
}

// ❌ Неправильно - валидация в контроллере
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [...]);
    if ($validator->fails()) { ... }
}
```

## ✅ Модели (app/Models/)

### Обязательные элементы:
- [ ] Явно объявить `$fillable` (предпочтительно) или `$guarded`
- [ ] НИКОГДА `$guarded = []` в продакшене
- [ ] Все связи с явным указанием типа возврата
- [ ] Использовать Eloquent Scopes для повторяющихся условий
- [ ] Аксессоры/мутаторы через `Attribute::make()` (Laravel 9+)
- [ ] Избегать N+1: всегда eager loading (`with()`, `load()`)

### Примеры:
```php
// ✅ Правильно
class Product extends Model
{
    protected $fillable = [
        'name', 'price', 'category_id', 'seller_id'
    ];
    
    // Связи с типами
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    // Scope для повторяющихся условий
    public function scopeVisible($query)
    {
        return $query->where('is_active', true)
            ->whereHas('seller.sellerPackages', function ($q) {
                $q->where('is_active', true);
            });
    }
    
    // Аксессор (Laravel 9+)
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name . ' - ' . $this->category->name
        );
    }
}

// ❌ Неправильно
class Product extends Model
{
    protected $guarded = []; // Опасно!
    
    // Нет типа возврата
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    // Старый синтаксис аксессора
    public function getFullNameAttribute()
    {
        return $this->name . ' - ' . $this->category->name;
    }
}
```

## ✅ Сервисы и бизнес-логика

### Структура:
- [ ] Бизнес-логика в классах `app/Services/` или `app/Actions/`
- [ ] Внедрение зависимостей через конструктор
- [ ] Не использовать фасады внутри доменных сервисов - принимать интерфейсы
- [ ] Регистрировать биндинги в `AppServiceProvider` при необходимости

### Примеры:
```php
// ✅ Правильно - app/Services/ProductService.php
class ProductService
{
    public function __construct(
        private ImageService $imageService,
        private CacheManager $cache
    ) {}
    
    public function createProduct(array $data): Product
    {
        $product = Product::create([
            'name' => $data['name'],
            'alias' => Str::slug($data['name']),
            // ...
        ]);
        
        if (isset($data['images'])) {
            $this->imageService->uploadImages($product, $data['images']);
        }
        
        $this->cache->forget('products_list');
        
        return $product;
    }
}

// ❌ Неправильно - бизнес-логика в контроллере
public function store(Request $request)
{
    // 150+ строк логики создания товара прямо в контроллере
}
```

## ✅ Миграции (database/migrations/)

### Правила:
- [ ] Миграции идемпотентны: проверять существование через `Schema::hasColumn()`
- [ ] Обязательные индексы и внешние ключи
- [ ] НИКОГДА не менять существующие миграции после применения
- [ ] Для изменений - создавать новую миграцию

### Примеры:
```php
// ✅ Правильно - идемпотентная миграция
public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        if (!Schema::hasColumn('products', 'is_featured')) {
            $table->boolean('is_featured')->default(false)->after('is_active');
        }
        
        if (!$table->hasIndex(['is_featured', 'created_at'])) {
            $table->index(['is_featured', 'created_at']);
        }
    });
}

// ❌ Неправильно - не проверяется существование
public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->boolean('is_featured')->default(false);
        // Упадет при повторном запуске
    });
}
```

## ✅ Авторизация

### Использовать Gates и Policies:
- [ ] Gates для простых проверок
- [ ] Policies для моделей (регистрация в `AuthServiceProvider`)
- [ ] В контроллере: `$this->authorize()` или `Gate::authorize()`
- [ ] В Blade: `@can` / `@cannot`

### Примеры:
```php
// ✅ Правильно - app/Policies/ProductPolicy.php
class ProductPolicy
{
    public function update(User $user, Product $product): bool
    {
        return $user->seller->id === $product->seller_id;
    }
}

// В AuthServiceProvider:
protected $policies = [
    Product::class => ProductPolicy::class,
];

// В контроллере:
public function edit(Product $product)
{
    $this->authorize('update', $product);
    // ...
}

// ❌ Неправильно - проверка вручную в контроллере
public function edit(Product $product)
{
    if (Auth::user()->seller->id !== $product->seller_id) {
        abort(403);
    }
}
```

## ✅ Очереди и события

### Правила:
- [ ] Тяжёлые операции (email, файлы) только через Jobs
- [ ] Бизнес-события через Events/Listeners
- [ ] Регистрация в `EventServiceProvider` или через `#[ListensTo]`

### Примеры:
```php
// ✅ Правильно - тяжелая операция в Job
class ProcessProductImages implements ShouldQueue
{
    use Dispatchable, Queueable;
    
    public function __construct(private Product $product) {}
    
    public function handle(ImageService $imageService): void
    {
        $imageService->processImages($this->product);
    }
}

// В контроллере:
ProcessProductImages::dispatch($product);

// ❌ Неправильно - синхронная обработка в контроллере
public function store(Request $request)
{
    // ... создание товара
    foreach ($request->images as $image) {
        // 5-10 секунд обработки на каждое изображение
        $this->processImage($image);
    }
}
```

## ✅ Кэш и производительность

### Правила:
- [ ] Использовать `Cache::remember()` для дорогих запросов
- [ ] Route cache только в продакшене (`php artisan route:cache`)
- [ ] Не делать запросы в цикле - использовать `whereIn()` или `with()`
- [ ] Всегда eager loading для связей

### Примеры:
```php
// ✅ Правильно
$products = Product::with(['images', 'seller', 'category'])
    ->visible()
    ->get();

$categories = Cache::remember('categories_tree', now()->addHours(24), function () {
    return Category::with('children')->get()->toTree();
});

// ❌ Неправильно
$products = Product::all();
foreach ($products as $product) {
    echo $product->seller->name; // N+1 проблема
    echo $product->category->name; // N+1 проблема
}
```

## ✅ Локализация

### Правила:
- [ ] Все строки через `__('key')` или `@lang('key')`
- [ ] Языковые файлы в `lang/ru/` и `lang/en/`
- [ ] НИКАКИХ строк на русском/английском в PHP или Blade

### Примеры:
```php
// ✅ Правильно
return redirect()->back()
    ->with('success', __('products.created_successfully'));

// В lang/ru/products.php:
return [
    'created_successfully' => 'Товар успешно создан',
];

// ❌ Неправильно
return redirect()->back()
    ->with('success', 'Товар успешно создан');
```

## ✅ Безопасность

### Правила:
- [ ] CSRF-защита включена (не отключать без причины)
- [ ] НИКОГДА `$request->all()` → только `$request->validated()`
- [ ] Rate limiting через `throttle:` middleware
- [ ] Переменные окружения через `config()`, не `env()`

### Примеры:
```php
// ✅ Правильно
public function store(StoreProductRequest $request)
{
    Product::create($request->validated());
}

$apiKey = config('services.stripe.key');

// ❌ Неправильно
Product::create($request->all()); // Опасно!
$apiKey = env('STRIPE_KEY'); // Не работает с config:cache
```

## ✅ Тесты

### Правила:
- [ ] Feature-тесты для HTTP-эндпоинтов
- [ ] Unit-тесты для изолированных сервисов
- [ ] Использовать `RefreshDatabase` или `LazilyRefreshDatabase`
- [ ] Фабрики для тестовых данных (не хардкод)

### Примеры:
```php
// ✅ Правильно - tests/Feature/ProductTest.php
class ProductTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_create_product(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->for($user)->create();
        
        $response = $this->actingAs($user)
            ->post(route('account.store.products.store'), [
                'name' => 'Test Product',
                'price' => 100,
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('products', ['name' => 'Test Product']);
    }
}
```

## 🔧 Команды для проверки

```bash
# Проверка стиля кода
vendor/bin/pint --test

# Автоисправление стиля
vendor/bin/pint

# Запуск тестов
php artisan test

# Проверка типов (PHPStan - после установки)
vendor/bin/phpstan analyse
```

## 📚 Полезные ссылки

- [Полная документация проекта](./project_architecture.md)
- [Отчет о соответствии кода](./code_compliance_report.md)
- [Инструкция по разработке](./../.github/copilot-instructions.md)

---

**Версия чек-листа**: 1.0  
**Дата**: 03.06.2026
