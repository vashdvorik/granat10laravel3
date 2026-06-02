# Приоритетные задачи для исправления кода

## 🎯 Цель
Привести код проекта в соответствие с Laravel best practices согласно инструкции `.github/copilot-instructions.md`

---

## 🔴 КРИТИЧНО - Должно быть исправлено НЕМЕДЛЕННО

### Задача 1: Добавить `declare(strict_types=1);` во все PHP-файлы
**Приоритет**: 🔴 Критичный  
**Оценка времени**: 1-2 часа  
**Затронутые файлы**: ~50+ файлов в `app/`, `database/`, `tests/`

**Описание**:
Все PHP-файлы должны начинаться с `declare(strict_types=1);` после открывающего тега `<?php`.

**Пример исправления**:
```php
// ❌ Было:
<?php

namespace App\Models;

// ✅ Должно быть:
<?php

declare(strict_types=1);

namespace App\Models;
```

**Автоматизация**:
```bash
# Создать скрипт для автоматического добавления
find app database tests -name "*.php" -exec sed -i '2i\\ndeclare(strict_types=1);' {} \;
```

**Файлы для исправления**:
- [ ] `app/Models/*.php` (7 файлов)
- [ ] `app/Http/Controllers/**/*.php` (10+ файлов)
- [ ] `app/Providers/*.php` (5 файлов)
- [ ] `app/Jobs/*.php` (1 файл)
- [ ] `app/Listeners/*.php` (3 файла)
- [ ] `database/migrations/*.php` (20+ файлов)
- [ ] `tests/**/*.php` (все тесты)

---

### Задача 2: Создать Form Request классы для всех форм
**Приоритет**: 🔴 Критичный  
**Оценка времени**: 4-6 часов  
**Затронутые контроллеры**: `ProductController`, `CabinetController`, `SellerController`, `UserProfileController`

**Создать классы**:

#### 2.1. `StoreProductRequest` и `UpdateProductRequest`
```bash
php artisan make:request StoreProductRequest
php artisan make:request UpdateProductRequest
```

**Содержание `StoreProductRequest.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Только пользователи с магазином могут создавать товары
        return $this->user()->seller !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'lowprice' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'category_id' => ['required', 'exists:categories,id'],
            'desc' => ['nullable', 'string'],
            'features1' => ['nullable', 'string', 'max:255'],
            'features2' => ['nullable', 'string', 'max:255'],
            'features3' => ['nullable', 'string', 'max:255'],
            'features4' => ['nullable', 'string', 'max:255'],
            'is_vip' => ['boolean'],
            'is_active' => ['boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.product.name.required'),
            'price.required' => __('validation.product.price.required'),
            'lowprice.lt' => __('validation.product.lowprice.less_than_price'),
            // ... остальные сообщения
        ];
    }
}
```

**Применить в контроллере**:
```php
// Backend\ProductController::store()
public function store(StoreProductRequest $request)
{
    $product = $this->productService->createProduct($request->validated());
    
    return redirect()->route('account.store.products.index')
        ->with('success', __('products.created_successfully'));
}
```

#### 2.2. Другие Request классы:
- [ ] `UpdateProductRequest` - редактирование товара
- [ ] `StoreSellerRequest` - создание магазина
- [ ] `UpdateSellerRequest` - редактирование магазина
- [ ] `UpdateUserProfileRequest` - редактирование профиля
- [ ] `UpdatePasswordRequest` - смена пароля

---

### Задача 3: Вынести бизнес-логику в Service/Action классы
**Приоритет**: 🔴 Критичный  
**Оценка времени**: 8-12 часов  
**Затронутые контроллеры**: `Backend\ProductController`, `Backend\PackageController`, `CabinetController`

#### 3.1. Создать `ProductService`
```bash
mkdir -p app/Services
touch app/Services/ProductService.php
```

**Содержание `ProductService.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Seller;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        private ImageService $imageService
    ) {}

    /**
     * Создать товар
     */
    public function createProduct(Seller $seller, array $data): Product
    {
        // Проверка лимитов тарифа
        $canActivate = $seller->canActivateProduct();
        if (!$canActivate['allowed'] && ($data['is_active'] ?? false)) {
            throw new \Exception($canActivate['message']);
        }

        // Создание товара
        $product = Product::create([
            'seller_id' => $seller->id,
            'name' => $data['name'],
            'alias' => $this->generateUniqueAlias($data['name']),
            'price' => $data['price'],
            'lowprice' => $data['lowprice'] ?? null,
            'category_id' => $data['category_id'],
            'desc' => $data['desc'] ?? null,
            'features1' => $data['features1'] ?? null,
            'features2' => $data['features2'] ?? null,
            'features3' => $data['features3'] ?? null,
            'features4' => $data['features4'] ?? null,
            'is_active' => $data['is_active'] ?? false,
            'is_vip' => $data['is_vip'] ?? false,
        ]);

        // Обработка изображений
        if (isset($data['images'])) {
            $this->imageService->uploadImages($product, $data['images']);
        }

        // Очистка кешей
        Product::clearProductCaches($product->category);

        return $product;
    }

    /**
     * Обновить товар
     */
    public function updateProduct(Product $product, array $data): Product
    {
        // Проверка изменения is_active
        if (isset($data['is_active']) && $data['is_active'] && !$product->is_active) {
            $canActivate = $product->seller->canActivateProduct($product->id);
            if (!$canActivate['allowed']) {
                throw new \Exception($canActivate['message']);
            }
        }

        $oldCategoryId = $product->category_id;

        $product->update([
            'name' => $data['name'] ?? $product->name,
            'price' => $data['price'] ?? $product->price,
            'lowprice' => $data['lowprice'] ?? $product->lowprice,
            'category_id' => $data['category_id'] ?? $product->category_id,
            // ... остальные поля
        ]);

        // Очистка кешей
        Product::clearProductCaches($product->category, $oldCategoryId);

        return $product;
    }

    /**
     * Генерировать уникальный alias
     */
    private function generateUniqueAlias(string $name): string
    {
        $alias = Str::slug($name);
        $count = 1;

        while (Product::where('alias', $alias)->exists()) {
            $alias = Str::slug($name) . '-' . $count;
            $count++;
        }

        return $alias;
    }
}
```

#### 3.2. Создать `ImageService`
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Image as ProductImage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * Загрузить изображения для товара
     */
    public function uploadImages(Product $product, array $images): void
    {
        $isFirst = $product->images()->count() === 0;

        foreach ($images as $index => $imageFile) {
            $filename = uniqid('product_') . '.' . $imageFile->getClientOriginalExtension();
            $path = $imageFile->storeAs('public/products', $filename);

            // Создание миниатюры
            $img = Image::make($imageFile);
            $img->resize(800, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->save(storage_path('app/public/products/' . $filename), 80);

            // Сохранение в БД
            ProductImage::create([
                'product_id' => $product->id,
                'img' => 'products/' . $filename,
                'is_main' => $isFirst && $index === 0,
            ]);
        }
    }

    /**
     * Удалить изображение
     */
    public function deleteImage(ProductImage $image): void
    {
        Storage::delete('public/' . $image->img);
        
        $wasMain = $image->is_main;
        $productId = $image->product_id;
        
        $image->delete();

        // Если удалено главное, назначить новое
        if ($wasMain) {
            $newMain = ProductImage::where('product_id', $productId)->first();
            if ($newMain) {
                $newMain->update(['is_main' => true]);
            }
        }
    }

    /**
     * Установить главное изображение
     */
    public function setMainImage(ProductImage $image): void
    {
        // Снять флаг is_main со всех изображений товара
        ProductImage::where('product_id', $image->product_id)
            ->update(['is_main' => false]);

        // Установить для текущего
        $image->update(['is_main' => true]);
    }
}
```

#### 3.3. Создать другие сервисы:
- [ ] `PackageService` - покупка и управление тарифами
- [ ] `SellerService` - управление магазином
- [ ] `CacheService` - централизованная работа с кешем

---

### Задача 4: Создать Policies для авторизации
**Приоритет**: 🔴 Критичный  
**Оценка времени**: 2-3 часа

#### 4.1. Создать `ProductPolicy`
```bash
php artisan make:policy ProductPolicy --model=Product
```

**Содержание**:
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Просмотр товара
     */
    public function view(?User $user, Product $product): bool
    {
        return true; // Все могут просматривать
    }

    /**
     * Создание товара
     */
    public function create(User $user): bool
    {
        return $user->seller !== null;
    }

    /**
     * Редактирование товара
     */
    public function update(User $user, Product $product): bool
    {
        return $user->seller->id === $product->seller_id;
    }

    /**
     * Удаление товара
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->seller->id === $product->seller_id;
    }

    /**
     * Активация/деактивация товара
     */
    public function toggleStatus(User $user, Product $product): bool
    {
        return $user->seller->id === $product->seller_id;
    }
}
```

#### 4.2. Зарегистрировать в `AuthServiceProvider`
```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    Product::class => ProductPolicy::class,
    Seller::class => SellerPolicy::class,
];
```

#### 4.3. Применить в контроллерах
```php
// Backend\ProductController
public function edit(Product $product)
{
    $this->authorize('update', $product);
    
    // ...
}

public function destroy(Product $product)
{
    $this->authorize('delete', $product);
    
    // ...
}
```

#### 4.4. Создать другие Policies:
- [ ] `SellerPolicy` - авторизация для магазина

---

### Задача 5: Вынести строки в языковые файлы
**Приоритет**: 🔴 Критичный  
**Оценка времени**: 4-6 часов

#### 5.1. Создать файлы локализации
```bash
# Создать структуру
mkdir -p lang/ru
mkdir -p lang/en
```

#### 5.2. Создать `lang/ru/products.php`
```php
<?php

return [
    'created_successfully' => 'Товар успешно создан',
    'updated_successfully' => 'Товар успешно обновлен',
    'deleted_successfully' => 'Товар успешно удален',
    'activated_successfully' => 'Товар активирован',
    'deactivated_successfully' => 'Товар деактивирован',
    'pushed_up_successfully' => 'Товар поднят в списке',
    
    'errors' => [
        'not_found' => 'Товар не найден',
        'limit_reached' => 'Достигнут лимит активных товаров',
        'vip_limit_reached' => 'Достигнут лимит VIP товаров',
        'no_active_package' => 'У вас нет активного тарифного пакета',
    ],
];
```

#### 5.3. Создать `lang/ru/validation.php` (кастомные сообщения)
```php
<?php

return [
    'product' => [
        'name' => [
            'required' => 'Поле "Название товара" обязательно для заполнения',
            'max' => 'Название товара не может быть длиннее :max символов',
        ],
        'price' => [
            'required' => 'Укажите цену товара',
            'numeric' => 'Цена должна быть числом',
            'min' => 'Цена не может быть отрицательной',
        ],
        'lowprice' => [
            'lt' => 'Цена со скидкой должна быть меньше обычной цены',
        ],
    ],
    
    'seller' => [
        'name' => [
            'required' => 'Укажите название магазина',
        ],
        // ...
    ],
];
```

#### 5.4. Заменить строки в коде
```php
// ❌ Было:
return redirect()->back()->with('success', 'Товар успешно создан');

// ✅ Стало:
return redirect()->back()->with('success', __('products.created_successfully'));
```

#### 5.5. Файлы для создания:
- [ ] `lang/ru/products.php`
- [ ] `lang/ru/sellers.php`
- [ ] `lang/ru/packages.php`
- [ ] `lang/ru/auth.php`
- [ ] `lang/ru/validation.php`

---

## 🟡 СРЕДНИЙ ПРИОРИТЕТ - Желательно исправить в ближайшее время

### Задача 6: Использовать `Route::resource` для CRUD
**Приоритет**: 🟡 Средний  
**Оценка времени**: 1 час

**Файл**: `routes/web.php`

**Изменения**:
```php
// ❌ Было:
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [Backend\ProductController::class, 'index'])->name('index');
    Route::get('create', [Backend\ProductController::class, 'create'])->name('create');
    Route::post('/', [Backend\ProductController::class, 'store'])->name('store');
    Route::get('{product}', [Backend\ProductController::class, 'show'])->name('show');
    Route::get('{product}/edit', [Backend\ProductController::class, 'edit'])->name('edit');
    Route::put('{product}', [Backend\ProductController::class, 'update'])->name('update');
    Route::delete('{product}', [Backend\ProductController::class, 'destroy'])->name('destroy');
});

// ✅ Должно быть:
Route::resource('products', Backend\ProductController::class);

// Дополнительные методы отдельно:
Route::patch('products/{product}/activate', [Backend\ProductController::class, 'activate'])
    ->name('products.activate');
Route::patch('products/{product}/deactivate', [Backend\ProductController::class, 'deactivate'])
    ->name('products.deactivate');
Route::post('products/{product}/push-up', [Backend\ProductController::class, 'pushUp'])
    ->name('products.push-up');
```

---

### Задача 7: Обновить аксессоры до Laravel 9+ синтаксиса
**Приоритет**: 🟡 Средний  
**Оценка времени**: 30 минут

**Файл**: `app/Models/Category.php`

```php
// ❌ Было:
public function getBreadcrumbsAttribute()
{
    return $this->getAncestors()->push($this);
}

// ✅ Должно быть:
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function breadcrumbs(): Attribute
{
    return Attribute::make(
        get: fn () => $this->getAncestors()->push($this)
    );
}
```

---

### Задача 8: Добавить базовые тесты
**Приоритет**: 🟡 Средний  
**Оценка времени**: 8-12 часов

#### 8.1. Тесты для системы тарифов
```php
// tests/Feature/PackageTest.php
public function test_user_can_purchase_package(): void
{
    $user = User::factory()->create(['balance' => 1000]);
    $seller = Seller::factory()->for($user)->create();
    $package = Package::factory()->create(['price' => 500]);
    
    $response = $this->actingAs($user)
        ->post(route('account.packages.purchase', $package));
    
    $response->assertRedirect();
    $this->assertEquals(500, $user->fresh()->balance);
    $this->assertDatabaseHas('seller_packages', [
        'seller_id' => $seller->id,
        'package_id' => $package->id,
        'is_active' => true,
    ]);
}
```

#### 8.2. Тесты для CRUD товаров
```php
// tests/Feature/ProductTest.php
public function test_user_can_create_product(): void { ... }
public function test_product_requires_active_package(): void { ... }
public function test_user_cannot_exceed_product_limit(): void { ... }
```

---

## 📊 Прогресс выполнения

### Критичные задачи:
- [ ] Задача 1: `declare(strict_types=1);` (0/50 файлов)
- [ ] Задача 2: Form Requests (0/6 классов)
- [ ] Задача 3: Service классы (0/4 сервисов)
- [ ] Задача 4: Policies (0/2 политик)
- [ ] Задача 5: Локализация (0/5 файлов)

### Средние задачи:
- [ ] Задача 6: Route::resource (0/3 маршрутов)
- [ ] Задача 7: Аксессоры (0/1 модель)
- [ ] Задача 8: Тесты (0/10 тестов)

**Общий прогресс**: 0% (0/8 задач)

---

## 📝 Порядок выполнения

1. **Начать с Задачи 1** - добавить `declare(strict_types=1);` во все файлы
2. **Задача 5** - создать языковые файлы (чтобы использовать в новых Request/Service)
3. **Задача 2** - создать Form Request классы
4. **Задача 3** - создать Service классы
5. **Задача 4** - создать Policies
6. **Задача 6** - оптимизировать маршруты
7. **Задача 7** - обновить аксессоры
8. **Задача 8** - добавить тесты

---

## 🚀 Быстрый старт

```bash
# 1. Создать ветку для рефакторинга
git checkout -b refactor/laravel-best-practices

# 2. Добавить declare(strict_types=1) (автоматизация)
# Создать скрипт add-strict-types.sh

# 3. Создать структуру для новых классов
mkdir -p app/Services
mkdir -p app/Policies
mkdir -p lang/ru
mkdir -p lang/en

# 4. Запустить тесты перед изменениями
php artisan test

# 5. Начать с самых критичных задач
```

---

**Дата создания**: 03.06.2026  
**Версия**: 1.0  
**Следующий review**: после выполнения критичных задач
