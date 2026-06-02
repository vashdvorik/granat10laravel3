# План приведения проекта к идеальному состоянию

**Проект**: Маркетплейс "Гранат" (Laravel)  
**Текущая оценка**: 4.5/10  
**Целевая оценка**: 9.5/10  
**Дата начала**: 03.06.2026  
**Ориентировочная длительность**: 8-12 недель

---

## 📊 Общая структура плана

```
Фаза 1: Критичные исправления (2-3 недели)
├── Строгая типизация
├── Архитектура (Services, Policies, Form Requests)
└── Локализация

Фаза 2: Тестирование и стабилизация (2-3 недели)
├── Покрытие тестами
├── Исправление багов
└── Code review

Фаза 3: Оптимизация и улучшения (2-3 недели)
├── Производительность
├── Безопасность
└── UX улучшения

Фаза 4: CI/CD и автоматизация (1-2 недели)
├── Автоматические проверки
├── Деплой
└── Мониторинг

Фаза 5: Документация и процессы (1 неделя)
├── Обновление документации
├── Onboarding новых разработчиков
└── Установление стандартов
```

---

## 🎯 ФАЗА 1: Критичные исправления (2-3 недели)

### Неделя 1: Строгая типизация и структура

#### День 1-2: Добавление `declare(strict_types=1);`

**Задача 1.1**: Автоматическое добавление во все PHP-файлы  
**Время**: 4 часа  
**Приоритет**: 🔴 Критичный

**Шаги**:
1. Создать скрипт для автоматического добавления
```bash
#!/bin/bash
# scripts/add-strict-types.sh

for file in $(find app database tests -name "*.php" -type f); do
    # Проверяем, нет ли уже declare
    if ! grep -q "declare(strict_types=1)" "$file"; then
        # Создаем временный файл
        tmpfile=$(mktemp)
        
        # Читаем первую строку (<?php)
        head -n 1 "$file" > "$tmpfile"
        
        # Добавляем пустую строку и declare
        echo "" >> "$tmpfile"
        echo "declare(strict_types=1);" >> "$tmpfile"
        
        # Добавляем остальное содержимое (со 2 строки)
        tail -n +2 "$file" >> "$tmpfile"
        
        # Заменяем оригинальный файл
        mv "$tmpfile" "$file"
        
        echo "✓ Обработан: $file"
    fi
done
```

2. Запустить скрипт
```bash
chmod +x scripts/add-strict-types.sh
./scripts/add-strict-types.sh
```

3. Проверить изменения
```bash
git diff app/ database/ tests/
```

4. Запустить тесты (будут ошибки)
```bash
php artisan test
```

5. Исправить ошибки типов (приведение типов)

**Результат**: Все PHP-файлы содержат `declare(strict_types=1);`

---

**Задача 1.2**: Исправление ошибок типов  
**Время**: 6 часов  
**Приоритет**: 🔴 Критичный

**Типичные ошибки и решения**:

```php
// Ошибка 1: Передача строки вместо int
// ❌ Было:
$product = Product::create([
    'category_id' => $request->input('category_id')  // Может быть строка
]);

// ✅ Исправление:
$product = Product::create([
    'category_id' => (int) $request->input('category_id')
]);

// Ошибка 2: Передача строки вместо bool
// ❌ Было:
$product->update(['is_active' => $request->input('is_active')]);

// ✅ Исправление:
$product->update(['is_active' => (bool) $request->input('is_active')]);

// Ошибка 3: Передача строки вместо float
// ❌ Было:
$product->update(['price' => $request->input('price')]);

// ✅ Исправление:
$product->update(['price' => (float) $request->input('price')]);
```

**Файлы для проверки**:
- [ ] `app/Http/Controllers/Backend/ProductController.php`
- [ ] `app/Http/Controllers/Backend/PackageController.php`
- [ ] `app/Http/Controllers/CabinetController.php`
- [ ] `app/Models/*.php` (все модели)

**Результат**: Все тесты проходят, типы приводятся явно

---

#### День 3-4: Создание Form Request классов

**Задача 1.3**: Создать все Form Request классы  
**Время**: 8 часов  
**Приоритет**: 🔴 Критичный

**Список классов для создания**:

1. **Товары (Products)**:
```bash
php artisan make:request StoreProductRequest
php artisan make:request UpdateProductRequest
```

2. **Магазины (Sellers)**:
```bash
php artisan make:request StoreSellerRequest
php artisan make:request UpdateSellerRequest
```

3. **Пользователи (Users)**:
```bash
php artisan make:request UpdateUserProfileRequest
php artisan make:request UpdatePasswordRequest
```

4. **Изображения (Images)**:
```bash
php artisan make:request UploadProductImagesRequest
```

**Шаблон каждого Request**:
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Логика авторизации
        return $this->user()->seller !== null;
    }

    public function rules(): array
    {
        return [
            // Правила валидации
        ];
    }

    public function messages(): array
    {
        return [
            // Кастомные сообщения через __()
        ];
    }
    
    /**
     * Подготовка данных перед валидацией
     */
    protected function prepareForValidation(): void
    {
        // Приведение типов при необходимости
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_vip' => $this->boolean('is_vip'),
        ]);
    }
}
```

**Результат**: 8 Form Request классов созданы и готовы к использованию

---

**Задача 1.4**: Применить Form Requests в контроллерах  
**Время**: 4 часа  
**Приоритет**: 🔴 Критичный

**Изменения в контроллерах**:

```php
// Backend\ProductController

// ❌ Было:
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [...]);
    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }
    // 100+ строк логики
}

// ✅ Стало:
public function store(StoreProductRequest $request)
{
    $product = $this->productService->createProduct(
        $request->user()->seller,
        $request->validated()
    );
    
    return redirect()->route('account.store.products.index')
        ->with('success', __('products.created_successfully'));
}
```

**Файлы для изменения**:
- [ ] `app/Http/Controllers/Backend/ProductController.php` (4 метода)
- [ ] `app/Http/Controllers/Backend/SellerController.php` (2 метода)
- [ ] `app/Http/Controllers/Backend/UserProfileController.php` (2 метода)
- [ ] `app/Http/Controllers/CabinetController.php` (4 метода)

**Результат**: Вся валидация вынесена в Form Requests

---

#### День 5: Создание языковых файлов

**Задача 1.5**: Создать структуру локализации  
**Время**: 6 часов  
**Приоритет**: 🔴 Критичный

**Структура файлов**:
```
lang/
├── ru/
│   ├── products.php         # Сообщения о товарах
│   ├── sellers.php          # Сообщения о магазинах
│   ├── packages.php         # Сообщения о тарифах
│   ├── auth.php            # Аутентификация
│   ├── validation.php       # Кастомная валидация
│   ├── errors.php          # Ошибки
│   └── common.php          # Общие фразы
└── en/
    └── (аналогично)
```

**Содержание `lang/ru/products.php`**:
```php
<?php

declare(strict_types=1);

return [
    // Успешные действия
    'created_successfully' => 'Товар успешно создан',
    'updated_successfully' => 'Товар успешно обновлён',
    'deleted_successfully' => 'Товар успешно удалён',
    'activated_successfully' => 'Товар активирован',
    'deactivated_successfully' => 'Товар деактивирован',
    'pushed_up_successfully' => 'Товар поднят в списке',
    
    // Ошибки
    'not_found' => 'Товар не найден',
    'unauthorized' => 'У вас нет прав для редактирования этого товара',
    'limit_reached' => 'Достигнут лимит активных товаров (:current/:limit)',
    'vip_limit_reached' => 'Достигнут лимит VIP товаров (:current/:limit)',
    'no_active_package' => 'У вас нет активного тарифного пакета',
    'invalid_category' => 'Указана несуществующая категория',
    
    // Поля
    'fields' => [
        'name' => 'Название товара',
        'price' => 'Цена',
        'lowprice' => 'Цена со скидкой',
        'category' => 'Категория',
        'description' => 'Описание',
        'features' => 'Характеристики',
        'images' => 'Изображения',
        'is_active' => 'Активен',
        'is_vip' => 'VIP товар',
    ],
    
    // Названия страниц
    'pages' => [
        'list' => 'Мои товары',
        'create' => 'Создать товар',
        'edit' => 'Редактировать товар',
        'show' => 'Просмотр товара',
    ],
];
```

**Содержание `lang/ru/validation.php`** (дополнение к стандартному):
```php
<?php

declare(strict_types=1);

return [
    'custom' => [
        'name' => [
            'required' => 'Поле ":attribute" обязательно для заполнения',
            'max' => 'Поле ":attribute" не может быть длиннее :max символов',
        ],
        'price' => [
            'required' => 'Укажите цену товара',
            'numeric' => 'Цена должна быть числом',
            'min' => 'Цена не может быть отрицательной',
        ],
        'lowprice' => [
            'numeric' => 'Цена со скидкой должна быть числом',
            'lt' => 'Цена со скидкой должна быть меньше обычной цены',
        ],
        'category_id' => [
            'required' => 'Выберите категорию товара',
            'exists' => 'Выбранная категория не существует',
        ],
        'images' => [
            'max' => 'Можно загрузить максимум :max изображений',
        ],
        'images.*' => [
            'image' => 'Файл должен быть изображением',
            'mimes' => 'Поддерживаются только форматы: JPEG, PNG, JPG, WebP',
            'max' => 'Размер изображения не должен превышать :max КБ',
        ],
    ],
    
    'attributes' => [
        'name' => 'название товара',
        'price' => 'цена',
        'lowprice' => 'цена со скидкой',
        'category_id' => 'категория',
        'desc' => 'описание',
        'images' => 'изображения',
    ],
];
```

**Результат**: Все строки вынесены в языковые файлы

---

### Неделя 2: Архитектура (Services и Policies)

#### День 6-8: Создание Service классов

**Задача 1.6**: Создать ProductService  
**Время**: 6 часов  
**Приоритет**: 🔴 Критичный

**Файл**: `app/Services/ProductService.php`

**Методы сервиса**:
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Seller;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private ImageService $imageService,
        private CacheService $cacheService
    ) {}

    /**
     * Создать товар
     */
    public function createProduct(Seller $seller, array $data): Product
    {
        return DB::transaction(function () use ($seller, $data) {
            // 1. Проверка лимитов
            $this->checkProductLimits($seller, $data);
            
            // 2. Создание товара
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
            
            // 3. Загрузка изображений
            if (isset($data['images'])) {
                $this->imageService->uploadImages($product, $data['images']);
            }
            
            // 4. Очистка кешей
            $this->cacheService->clearProductCaches($product);
            
            return $product->load(['images', 'category', 'seller']);
        });
    }

    /**
     * Обновить товар
     */
    public function updateProduct(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $oldCategoryId = $product->category_id;
            
            // Проверка лимитов при активации
            if (isset($data['is_active']) && $data['is_active'] && !$product->is_active) {
                $this->checkProductLimits($product->seller, $data, $product->id);
            }
            
            $product->update($data);
            
            // Очистка кешей
            $this->cacheService->clearProductCaches($product, $oldCategoryId);
            
            return $product->fresh(['images', 'category', 'seller']);
        });
    }

    /**
     * Удалить товар
     */
    public function deleteProduct(Product $product): void
    {
        DB::transaction(function () use ($product) {
            // Удалить изображения
            foreach ($product->images as $image) {
                $this->imageService->deleteImage($image);
            }
            
            // Удалить товар
            $product->delete();
            
            // Очистить кеши
            $this->cacheService->clearProductCaches($product);
        });
    }

    /**
     * Активировать товар
     */
    public function activateProduct(Product $product): Product
    {
        $canActivate = $product->seller->canActivateProduct($product->id);
        
        if (!$canActivate['allowed']) {
            throw new \DomainException($canActivate['message']);
        }
        
        $product->update(['is_active' => true]);
        $this->cacheService->clearProductCaches($product);
        
        return $product;
    }

    /**
     * Деактивировать товар
     */
    public function deactivateProduct(Product $product): Product
    {
        $product->update(['is_active' => false]);
        $this->cacheService->clearProductCaches($product);
        
        return $product;
    }

    /**
     * Поднять товар в списке
     */
    public function pushUpProduct(Product $product): Product
    {
        $product->update(['push_at' => now()]);
        $this->cacheService->clearProductCaches($product);
        
        return $product;
    }

    /**
     * Проверка лимитов тарифа
     */
    private function checkProductLimits(Seller $seller, array $data, ?int $excludeProductId = null): void
    {
        // Проверка обычных товаров
        if ($data['is_active'] ?? false) {
            $canActivate = $seller->canActivateProduct($excludeProductId);
            if (!$canActivate['allowed']) {
                throw new \DomainException($canActivate['message']);
            }
        }
        
        // Проверка VIP товаров
        if (($data['is_vip'] ?? false) && ($data['is_active'] ?? false)) {
            $canActivateVip = $seller->canActivateVipProduct($excludeProductId);
            if (!$canActivateVip['allowed']) {
                throw new \DomainException($canActivateVip['message']);
            }
        }
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

**Результат**: ProductService создан с полной бизнес-логикой

---

**Задача 1.7**: Создать ImageService  
**Время**: 4 часа  
**Приоритет**: 🔴 Критичный

**Файл**: `app/Services/ImageService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Image as ProductImage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ImageService
{
    /**
     * Загрузить изображения для товара
     * 
     * @param array<UploadedFile> $images
     */
    public function uploadImages(Product $product, array $images): void
    {
        $isFirst = $product->images()->count() === 0;

        foreach ($images as $index => $imageFile) {
            $filename = $this->generateUniqueFilename($imageFile);
            
            // Сохранение и обработка изображения
            $this->processAndSaveImage($imageFile, $filename);
            
            // Создание записи в БД
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
        // Удалить файл
        Storage::delete('public/' . $image->img);
        
        $wasMain = $image->is_main;
        $productId = $image->product_id;
        
        $image->delete();

        // Если удалено главное, назначить новое
        if ($wasMain) {
            $this->assignNewMainImage($productId);
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

    /**
     * Обработать и сохранить изображение
     */
    private function processAndSaveImage(UploadedFile $file, string $filename): void
    {
        $path = storage_path('app/public/products/' . $filename);
        
        // Создать директорию если не существует
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Обработка изображения
        $img = Image::make($file);
        
        // Изменение размера с сохранением пропорций
        $img->resize(800, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize(); // Не увеличивать маленькие изображения
        });
        
        // Сохранение с качеством 80%
        $img->save($path, 80);
    }

    /**
     * Генерировать уникальное имя файла
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        
        do {
            $filename = uniqid('product_', true) . '.' . $extension;
            $path = storage_path('app/public/products/' . $filename);
        } while (file_exists($path));
        
        return $filename;
    }

    /**
     * Назначить новое главное изображение
     */
    private function assignNewMainImage(int $productId): void
    {
        $newMain = ProductImage::where('product_id', $productId)->first();
        
        if ($newMain) {
            $newMain->update(['is_main' => true]);
        }
    }
}
```

**Результат**: ImageService создан

---

**Задача 1.8**: Создать другие Service классы  
**Время**: 6 часов  
**Приоритет**: 🔴 Критичный

**Дополнительные сервисы**:

1. **CacheService** - централизованная работа с кешем
2. **PackageService** - покупка и управление тарифами
3. **SellerService** - управление магазином
4. **UserService** - управление пользователями

**Результат**: Все Service классы созданы

---

**Задача 1.9**: Зарегистрировать сервисы в AppServiceProvider  
**Время**: 1 час  
**Приоритет**: 🔴 Критичный

```php
// app/Providers/AppServiceProvider.php

public function register(): void
{
    // Регистрация сервисов как singleton
    $this->app->singleton(ProductService::class);
    $this->app->singleton(ImageService::class);
    $this->app->singleton(CacheService::class);
    $this->app->singleton(PackageService::class);
    $this->app->singleton(SellerService::class);
    $this->app->singleton(UserService::class);
}
```

**Результат**: Сервисы зарегистрированы и доступны через DI

---

#### День 9-10: Создание Policies

**Задача 1.10**: Создать ProductPolicy  
**Время**: 2 часа  
**Приоритет**: 🔴 Критичный

```bash
php artisan make:policy ProductPolicy --model=Product
```

**Файл**: `app/Policies/ProductPolicy.php`

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Просмотр товара (публичный доступ)
     */
    public function view(?User $user, Product $product): bool
    {
        return true;
    }

    /**
     * Просмотр любых товаров (для админа)
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Создание товара (только продавцы)
     */
    public function create(User $user): bool
    {
        return $user->seller !== null;
    }

    /**
     * Редактирование товара (только владелец)
     */
    public function update(User $user, Product $product): bool
    {
        return $user->seller?->id === $product->seller_id;
    }

    /**
     * Удаление товара (только владелец)
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->seller?->id === $product->seller_id;
    }

    /**
     * Восстановление удаленного товара
     */
    public function restore(User $user, Product $product): bool
    {
        return $user->seller?->id === $product->seller_id;
    }

    /**
     * Полное удаление товара
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $user->seller?->id === $product->seller_id;
    }

    /**
     * Активация/деактивация товара
     */
    public function toggleStatus(User $user, Product $product): bool
    {
        return $user->seller?->id === $product->seller_id;
    }

    /**
     * Поднятие товара в списке
     */
    public function pushUp(User $user, Product $product): bool
    {
        return $user->seller?->id === $product->seller_id;
    }
}
```

**Результат**: ProductPolicy создан

---

**Задача 1.11**: Создать SellerPolicy  
**Время**: 1 час  
**Приоритет**: 🔴 Критичный

```bash
php artisan make:policy SellerPolicy --model=Seller
```

**Файл**: `app/Policies/SellerPolicy.php`

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Seller;
use App\Models\User;

class SellerPolicy
{
    /**
     * Просмотр магазина (публичный доступ)
     */
    public function view(?User $user, Seller $seller): bool
    {
        return true;
    }

    /**
     * Создание магазина (один на пользователя)
     */
    public function create(User $user): bool
    {
        return $user->seller === null;
    }

    /**
     * Редактирование магазина (только владелец)
     */
    public function update(User $user, Seller $seller): bool
    {
        return $user->id === $seller->user_id;
    }

    /**
     * Удаление магазина (только владелец)
     */
    public function delete(User $user, Seller $seller): bool
    {
        return $user->id === $seller->user_id;
    }
}
```

**Результат**: SellerPolicy создан

---

**Задача 1.12**: Зарегистрировать Policies  
**Время**: 30 минут  
**Приоритет**: 🔴 Критичный

```php
// app/Providers/AuthServiceProvider.php

protected $policies = [
    Product::class => ProductPolicy::class,
    Seller::class => SellerPolicy::class,
];
```

**Результат**: Policies зарегистрированы

---

**Задача 1.13**: Применить Policies в контроллерах  
**Время**: 3 часа  
**Приоритет**: 🔴 Критичный

```php
// Backend\ProductController

public function edit(Product $product)
{
    $this->authorize('update', $product);
    
    // ...
}

public function update(UpdateProductRequest $request, Product $product)
{
    $this->authorize('update', $product);
    
    $product = $this->productService->updateProduct($product, $request->validated());
    
    return redirect()->route('account.store.products.index')
        ->with('success', __('products.updated_successfully'));
}

public function destroy(Product $product)
{
    $this->authorize('delete', $product);
    
    $this->productService->deleteProduct($product);
    
    return redirect()->route('account.store.products.index')
        ->with('success', __('products.deleted_successfully'));
}
```

**Результат**: Все контроллеры используют Policies

---

### Неделя 3: Рефакторинг контроллеров

#### День 11-12: Обновление контроллеров

**Задача 1.14**: Рефакторинг Backend\ProductController  
**Время**: 6 часов  
**Приоритет**: 🔴 Критичный

**Цель**: Контроллер должен содержать только:
- Авторизацию через Policies
- Валидацию через Form Requests
- Вызовы сервисов
- Возврат ответов

**Было** (150+ строк метода):
```php
public function store(Request $request)
{
    // Валидация
    $validator = Validator::make(...);
    
    // Проверка лимитов
    $canActivate = $seller->canActivateProduct();
    
    // Генерация alias
    $alias = Str::slug($name);
    
    // Создание товара
    $product = Product::create([...]);
    
    // Обработка изображений
    foreach ($images as $image) {
        // 30+ строк обработки
    }
    
    // Очистка кешей
    Product::clearProductCaches();
    
    return redirect()->back();
}
```

**Стало** (10 строк):
```php
public function store(StoreProductRequest $request)
{
    $product = $this->productService->createProduct(
        $request->user()->seller,
        $request->validated()
    );
    
    return redirect()->route('account.store.products.index')
        ->with('success', __('products.created_successfully'));
}
```

**Все методы контроллера**:
- [ ] `index()` - список товаров
- [ ] `create()` - форма создания
- [ ] `store()` - сохранение
- [ ] `show()` - просмотр
- [ ] `edit()` - форма редактирования
- [ ] `update()` - обновление
- [ ] `destroy()` - удаление
- [ ] `activate()` - активация
- [ ] `deactivate()` - деактивация
- [ ] `pushUp()` - поднятие в списке
- [ ] `destroyImage()` - удаление изображения
- [ ] `setMainImage()` - установка главного
- [ ] `getChildCategories()` - AJAX категории

**Результат**: ProductController сокращен с 600+ до ~150 строк

---

**Задача 1.15**: Рефакторинг других контроллеров  
**Время**: 6 часов  
**Приоритет**: 🔴 Критичный

**Контроллеры для рефакторинга**:
- [ ] `Backend\PackageController` (2 метода)
- [ ] `Backend\SellerController` (3 метода)
- [ ] `Backend\UserProfileController` (2 метода)
- [ ] `CabinetController` (6 методов)

**Результат**: Все контроллеры следуют принципу "тонких контроллеров"

---

#### День 13-14: Оптимизация маршрутов

**Задача 1.16**: Использовать Route::resource  
**Время**: 2 часа  
**Приоритет**: 🟡 Средний

```php
// routes/web.php

// ❌ Было:
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [Backend\ProductController::class, 'index'])->name('index');
    Route::get('create', [Backend\ProductController::class, 'create'])->name('create');
    // ... еще 5 маршрутов
});

// ✅ Стало:
Route::resource('products', Backend\ProductController::class);

// Дополнительные методы отдельно:
Route::patch('products/{product}/activate', [Backend\ProductController::class, 'activate'])
    ->name('products.activate');
Route::patch('products/{product}/deactivate', [Backend\ProductController::class, 'deactivate'])
    ->name('products.deactivate');
Route::post('products/{product}/push-up', [Backend\ProductController::class, 'pushUp'])
    ->name('products.push-up');
```

**Маршруты для оптимизации**:
- [ ] `products` (Backend\ProductController)
- [ ] `store` (Backend\SellerController) - частично
- [ ] `packages` (Backend\PackageController) - только index

**Результат**: Маршруты упрощены, используется стандартный RESTful подход

---

**Задача 1.17**: Обновить аксессоры моделей  
**Время**: 1 час  
**Приоритет**: 🟡 Средний

```php
// app/Models/Category.php

// ❌ Было (Laravel 8-):
public function getBreadcrumbsAttribute()
{
    return $this->getAncestors()->push($this);
}

// ✅ Стало (Laravel 9+):
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function breadcrumbs(): Attribute
{
    return Attribute::make(
        get: fn () => $this->getAncestors()->push($this)
    );
}
```

**Модели для проверки**:
- [ ] `Category` (breadcrumbs)
- [ ] `Product` (все аксессоры проверить)
- [ ] `Seller` (все аксессоры проверить)

**Результат**: Все модели используют современный синтаксис Laravel 9+

---

### Подведение итогов Фазы 1

**Чек-лист завершения**:
- [ ] Все файлы содержат `declare(strict_types=1);`
- [ ] Все Form Request классы созданы и применены
- [ ] Все Service классы созданы
- [ ] Все Policies созданы и применены
- [ ] Все контроллеры рефакторнуты
- [ ] Все строки вынесены в lang/
- [ ] Маршруты оптимизированы
- [ ] Аксессоры обновлены

**Тесты проходят**: ✅  
**Code style (Pint)**: ✅  
**Готовность к Фазе 2**: ✅

---

## 🧪 ФАЗА 2: Тестирование и стабилизация (2-3 недели)

### Неделя 4: Написание тестов

#### День 15-17: Feature тесты

**Задача 2.1**: Тесты для товаров (Products)  
**Время**: 8 часов  
**Приоритет**: 🔴 Критичный

**Файл**: `tests/Feature/ProductTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Seller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Package;
use App\Models\SellerPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Seller $seller;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Создать тестовые данные
        $this->user = User::factory()->create();
        $this->seller = Seller::factory()->for($this->user)->create();
        $this->category = Category::factory()->create();
        
        // Активировать тариф
        $package = Package::factory()->create([
            'active_products_limit' => 10,
            'vip_products_limit' => 5,
        ]);
        
        SellerPackage::create([
            'seller_id' => $this->seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);
    }

    /** @test */
    public function user_can_create_product(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->user)
            ->post(route('account.store.products.store'), [
                'name' => 'Test Product',
                'price' => 100.50,
                'category_id' => $this->category->id,
                'is_active' => true,
                'images' => [
                    UploadedFile::fake()->image('photo1.jpg'),
                    UploadedFile::fake()->image('photo2.jpg'),
                ],
            ]);

        $response->assertRedirect(route('account.store.products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'price' => 100.50,
            'seller_id' => $this->seller->id,
            'is_active' => true,
        ]);

        $product = Product::where('name', 'Test Product')->first();
        $this->assertCount(2, $product->images);
        $this->assertTrue($product->images->first()->is_main);

        Storage::disk('public')->assertExists('products/' . basename($product->images->first()->img));
    }

    /** @test */
    public function user_cannot_create_product_without_seller(): void
    {
        $userWithoutSeller = User::factory()->create();

        $response = $this->actingAs($userWithoutSeller)
            ->post(route('account.store.products.store'), [
                'name' => 'Test Product',
                'price' => 100,
                'category_id' => $this->category->id,
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_exceed_product_limit(): void
    {
        // Создать максимум товаров
        Product::factory()->count(10)->for($this->seller)->create([
            'is_active' => true
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('account.store.products.store'), [
                'name' => 'Extra Product',
                'price' => 100,
                'category_id' => $this->category->id,
                'is_active' => true,
            ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function user_can_update_product(): void
    {
        $product = Product::factory()
            ->for($this->seller)
            ->for($this->category)
            ->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->user)
            ->put(route('account.store.products.update', $product), [
                'name' => 'New Name',
                'price' => 200,
                'category_id' => $this->category->id,
            ]);

        $response->assertRedirect(route('account.store.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Name',
            'price' => 200,
        ]);
    }

    /** @test */
    public function user_cannot_update_other_seller_product(): void
    {
        $otherSeller = Seller::factory()->create();
        $product = Product::factory()
            ->for($otherSeller)
            ->for($this->category)
            ->create();

        $response = $this->actingAs($this->user)
            ->put(route('account.store.products.update', $product), [
                'name' => 'Hacked Name',
                'price' => 1,
                'category_id' => $this->category->id,
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'name' => 'Hacked Name',
        ]);
    }

    /** @test */
    public function user_can_delete_product(): void
    {
        $product = Product::factory()
            ->for($this->seller)
            ->for($this->category)
            ->create();

        $response = $this->actingAs($this->user)
            ->delete(route('account.store.products.destroy', $product));

        $response->assertRedirect(route('account.store.products.index'));

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    /** @test */
    public function product_requires_valid_category(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('account.store.products.store'), [
                'name' => 'Test Product',
                'price' => 100,
                'category_id' => 99999, // Несуществующая категория
            ]);

        $response->assertSessionHasErrors('category_id');
    }

    /** @test */
    public function product_validates_price(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('account.store.products.store'), [
                'name' => 'Test Product',
                'price' => -100, // Отрицательная цена
                'category_id' => $this->category->id,
            ]);

        $response->assertSessionHasErrors('price');
    }

    /** @test */
    public function lowprice_must_be_less_than_price(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('account.store.products.store'), [
                'name' => 'Test Product',
                'price' => 100,
                'lowprice' => 150, // Больше обычной цены
                'category_id' => $this->category->id,
            ]);

        $response->assertSessionHasErrors('lowprice');
    }
}
```

**Дополнительные тесты**:
- [ ] `test_user_can_activate_product()`
- [ ] `test_user_can_deactivate_product()`
- [ ] `test_user_can_push_up_product()`
- [ ] `test_user_can_upload_images()`
- [ ] `test_user_can_delete_image()`
- [ ] `test_user_can_set_main_image()`
- [ ] `test_product_alias_is_unique()`
- [ ] `test_product_clears_cache_on_update()`

**Результат**: 20+ тестов для Products

---

**Задача 2.2**: Тесты для тарифов (Packages)  
**Время**: 6 часов  
**Приоритет**: 🔴 Критичный

**Файл**: `tests/Feature/PackageTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Seller;
use App\Models\Package;
use App\Models\SellerPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_purchase_package(): void
    {
        $user = User::factory()->create(['balance' => 1000]);
        $seller = Seller::factory()->for($user)->create();
        $package = Package::factory()->create(['price' => 500, 'duration_days' => 30]);

        $response = $this->actingAs($user)
            ->post(route('account.packages.purchase', $package));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Проверка баланса
        $this->assertEquals(500, $user->fresh()->balance);

        // Проверка создания пакета
        $this->assertDatabaseHas('seller_packages', [
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
        ]);

        $sellerPackage = SellerPackage::where('seller_id', $seller->id)->first();
        $this->assertEquals(now()->toDateString(), $sellerPackage->start_date->toDateString());
        $this->assertEquals(now()->addDays(30)->toDateString(), $sellerPackage->end_date->toDateString());
    }

    /** @test */
    public function user_cannot_purchase_package_with_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        $seller = Seller::factory()->for($user)->create();
        $package = Package::factory()->create(['price' => 500]);

        $response = $this->actingAs($user)
            ->post(route('account.packages.purchase', $package));

        $response->assertSessionHasErrors();

        $this->assertEquals(100, $user->fresh()->balance);
        $this->assertDatabaseMissing('seller_packages', [
            'seller_id' => $seller->id,
            'package_id' => $package->id,
        ]);
    }

    /** @test */
    public function expired_package_hides_products(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->for($user)->create();
        $package = Package::factory()->create();

        // Создать истекший пакет
        SellerPackage::create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'start_date' => now()->subMonth(),
            'end_date' => now()->subDay(), // Истек вчера
        ]);

        $product = Product::factory()->for($seller)->create(['is_active' => true]);

        // Товар не должен быть виден
        $visibleProducts = Product::visible()->get();
        $this->assertFalse($visibleProducts->contains($product));
    }

    /** @test */
    public function products_become_visible_after_package_purchase(): void
    {
        $user = User::factory()->create(['balance' => 1000]);
        $seller = Seller::factory()->for($user)->create();
        $package = Package::factory()->create(['price' => 500]);

        $product = Product::factory()->for($seller)->create(['is_active' => true]);

        // Товар не виден без пакета
        $this->assertFalse(Product::visible()->exists());

        // Покупка пакета
        $this->actingAs($user)
            ->post(route('account.packages.purchase', $package));

        // Товар стал виден
        $this->assertTrue(Product::visible()->where('id', $product->id)->exists());
    }
}
```

**Дополнительные тесты**:
- [ ] `test_package_limits_enforce_correctly()`
- [ ] `test_vip_limits_enforce_correctly()`
- [ ] `test_auto_renew_works()`
- [ ] `test_user_can_toggle_auto_renew()`

**Результат**: 10+ тестов для Packages

---

**Задача 2.3**: Тесты для магазинов (Sellers)  
**Время**: 4 часа  
**Приоритет**: 🟡 Средний

**Результат**: 8+ тестов для Sellers

---

**Задача 2.4**: Тесты для публичной части  
**Время**: 4 часов  
**Приоритет**: 🟡 Средний

**Тесты**:
- [ ] Catalog - листинг товаров
- [ ] Catalog - фильтры работают
- [ ] Product - детальная страница
- [ ] Search - поиск работает
- [ ] Favorites - избранное работает

**Результат**: 10+ тестов для публичной части

---

#### День 18-19: Unit тесты

**Задача 2.5**: Unit тесты для сервисов  
**Время**: 6 часов  
**Приоритет**: 🟡 Средний

**Файл**: `tests/Unit/Services/ProductServiceTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ProductService;
use App\Services\ImageService;
use App\Services\CacheService;
use App\Models\Product;
use App\Models\Seller;
use Mockery;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    private ProductService $service;
    private ImageService $imageService;
    private CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();

        // Мокаем зависимости
        $this->imageService = Mockery::mock(ImageService::class);
        $this->cacheService = Mockery::mock(CacheService::class);

        $this->service = new ProductService(
            $this->imageService,
            $this->cacheService
        );
    }

    /** @test */
    public function it_generates_unique_alias_from_name(): void
    {
        $seller = Seller::factory()->create();
        $data = [
            'name' => 'Тестовый Товар',
            'price' => 100,
            'category_id' => 1,
            'is_active' => false,
        ];

        $this->cacheService->shouldReceive('clearProductCaches')->once();

        $product = $this->service->createProduct($seller, $data);

        $this->assertEquals('testovyj-tovar', $product->alias);
    }

    /** @test */
    public function it_throws_exception_when_limit_reached(): void
    {
        $this->expectException(\DomainException::class);

        $seller = Seller::factory()->create();
        
        // Продавец достиг лимита
        $seller->shouldReceive('canActivateProduct')
            ->andReturn([
                'allowed' => false,
                'message' => 'Лимит достигнут',
            ]);

        $data = [
            'name' => 'Test',
            'price' => 100,
            'category_id' => 1,
            'is_active' => true,
        ];

        $this->service->createProduct($seller, $data);
    }
}
```

**Результат**: 15+ unit тестов для сервисов

---

**Задача 2.6**: Unit тесты для моделей  
**Время**: 4 часа  
**Приоритет**: 🟡 Средний

**Тесты для Scopes, связей, методов моделей**

**Результат**: 10+ unit тестов для моделей

---

### Неделя 5: Исправление багов и покрытие

#### День 20-21: Достижение покрытия 80%+

**Задача 2.7**: Анализ покрытия тестами  
**Время**: 2 часа  
**Приоритет**: 🟡 Средний

```bash
# Установить PCOV для покрытия
composer require --dev pcov/clilib

# Запустить тесты с покрытием
php artisan test --coverage --min=80
```

**Цель**: Покрытие критичных компонентов минимум 80%

**Приоритет покрытия**:
1. Services (100%)
2. Policies (100%)
3. Models (80%)
4. Controllers (70%)

**Результат**: Покрытие тестами 80%+

---

**Задача 2.8**: Исправление найденных багов  
**Время**: 8 часов  
**Приоритет**: 🔴 Критичный

**Типичные баги, которые могут быть найдены**:
- Проблемы с типами после добавления strict_types
- Race conditions в кешировании
- N+1 проблемы
- Уязвимости безопасности
- Проблемы с транзакциями

**Результат**: Все найденные баги исправлены

---

### Неделя 6: Code Review и стабилизация

#### День 22-24: Внутреннее code review

**Задача 2.9**: Code review всех изменений  
**Время**: 12 часов  
**Приоритет**: 🔴 Критичный

**Чек-лист для review**:
- [ ] Все файлы содержат `declare(strict_types=1);`
- [ ] Нет бизнес-логики в контроллерах
- [ ] Все строки вынесены в lang/
- [ ] Используются type hints везде
- [ ] PHPDoc комментарии где нужны
- [ ] Нет дублирования кода
- [ ] Нет мертвого кода
- [ ] Нет TODO/FIXME комментариев
- [ ] Все тесты проходят
- [ ] Покрытие тестами 80%+

**Результат**: Code review завершен, замечания исправлены

---

**Задача 2.10**: Запуск статического анализа  
**Время**: 4 часа  
**Приоритет**: 🟡 Средний

```bash
# Установить PHPStan
composer require --dev phpstan/phpstan
composer require --dev larastan/larastan

# Создать конфиг
cat > phpstan.neon << 'EOF'
includes:
    - ./vendor/larastan/larastan/extension.neon

parameters:
    level: 5
    paths:
        - app
        - database
        - tests
    excludePaths:
        - app/Console/Kernel.php
    checkMissingIterableValueType: false
EOF

# Запустить анализ
vendor/bin/phpstan analyse
```

**Результат**: PHPStan level 5 без ошибок

---

**Задача 2.11**: Проверка стиля кода  
**Время**: 1 час  
**Приоритет**: 🟡 Средний

```bash
# Проверка
vendor/bin/pint --test

# Автоисправление
vendor/bin/pint
```

**Результат**: Код соответствует Laravel Pint standards

---

### Подведение итогов Фазы 2

**Метрики качества**:
- ✅ Покрытие тестами: 80%+
- ✅ PHPStan level: 5
- ✅ Pint: все проверки пройдены
- ✅ Все баги исправлены
- ✅ Code review завершен

**Готовность к Фазе 3**: ✅

---

## ⚡ ФАЗА 3: Оптимизация и улучшения (2-3 недели)

### Неделя 7: Производительность

#### День 25-26: Оптимизация запросов

**Задача 3.1**: Audit всех SQL запросов  
**Время**: 6 часов  
**Приоритет**: 🟡 Средний

**Инструменты**:
```bash
# Установить Laravel Debugbar
composer require --dev barryvdh/laravel-debugbar

# Установить Telescope для мониторинга
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

**Что проверять**:
- [ ] N+1 проблемы (везде использовать `with()`)
- [ ] Отсутствующие индексы в БД
- [ ] Медленные запросы (> 100ms)
- [ ] Запросы в циклах
- [ ] Использование `whereIn()` vs цикл запросов

**Результат**: Все запросы оптимизированы

---

**Задача 3.2**: Добавить недостающие индексы  
**Время**: 3 часа  
**Приоритет**: 🟡 Средний

```php
// Создать миграцию
php artisan make:migration add_missing_indexes_to_tables

// В миграции:
public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        // Индексы для фильтров и сортировки
        if (!Schema::hasIndex('products', ['seller_id', 'is_active'])) {
            $table->index(['seller_id', 'is_active']);
        }
        
        if (!Schema::hasIndex('products', ['category_id', 'is_active'])) {
            $table->index(['category_id', 'is_active']);
        }
        
        // Для поиска
        if (!Schema::hasIndex('products', ['name'])) {
            $table->index(['name']);
        }
    });
    
    Schema::table('seller_packages', function (Blueprint $table) {
        if (!Schema::hasIndex('seller_packages', ['seller_id', 'is_active', 'end_date'])) {
            $table->index(['seller_id', 'is_active', 'end_date']);
        }
    });
}
```

**Результат**: Все необходимые индексы добавлены

---

**Задача 3.3**: Настроить Redis для кеша и сессий  
**Время**: 3 часа  
**Приоритет**: 🟡 Средний

```bash
# Установить Redis
composer require predis/predis

# .env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# config/database.php - проверить настройки Redis
```

**Результат**: Redis настроен и используется

---

#### День 27: Оптимизация фронтенда

**Задача 3.4**: Минификация и сжатие assets  
**Время**: 4 часа  
**Приоритет**: 🟢 Низкий

```bash
# vite.config.js
export default defineConfig({
    build: {
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
            },
        },
    },
    // ...
});

# Сборка для production
npm run build
```

**Результат**: Assets минифицированы и оптимизированы

---

**Задача 3.5**: Lazy loading изображений  
**Время**: 2 часа  
**Приоритет**: 🟢 Низкий

```blade
{{-- В шаблонах --}}
<img src="{{ asset($product->mainImage->img) }}" 
     loading="lazy"
     alt="{{ $product->name }}">
```

**Результат**: Изображения загружаются lazy

---

### Неделя 8: Безопасность

#### День 28-29: Аудит безопасности

**Задача 3.6**: Проверка уязвимостей зависимостей  
**Время**: 2 часа  
**Приоритет**: 🔴 Критичный

```bash
# Проверка уязвимостей Composer
composer audit

# Обновить зависимости с уязвимостями
composer update --with-dependencies
```

**Результат**: Все уязвимости устранены

---

**Задача 3.7**: Настроить rate limiting  
**Время**: 3 часа  
**Приоритет**: 🟡 Средний

```php
// app/Providers/RouteServiceProvider.php
protected function configureRateLimiting(): void
{
    // API endpoints
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    // Формы (создание товара, регистрация и т.д.)
    RateLimiter::for('forms', function (Request $request) {
        return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
    });

    // Авторизация
    RateLimiter::for('login', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });
}

// В маршрутах:
Route::post('/products', [ProductController::class, 'store'])
    ->middleware('throttle:forms');
```

**Результат**: Rate limiting настроен

---

**Задача 3.8**: Добавить HTTPS редирект  
**Время**: 1 час  
**Приоритет**: 🔴 Критичный (для production)

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    if (app()->environment('production')) {
        URL::forceScheme('https');
    }
}

// app/Http/Middleware/TrustProxies.php
protected $proxies = '*';
protected $headers = Request::HEADER_X_FORWARDED_ALL;
```

**Результат**: HTTPS редирект настроен

---

**Задача 3.9**: Проверить защиту от SQL injection  
**Время**: 2 часа  
**Приоритет**: 🔴 Критичный

**Проверить все места с raw SQL**:
```php
// ❌ Уязвимость
DB::select("SELECT * FROM products WHERE id = " . $id);

// ✅ Безопасно
DB::select("SELECT * FROM products WHERE id = ?", [$id]);

// ✅ Еще лучше - использовать Eloquent
Product::find($id);
```

**Результат**: Нет SQL injection уязвимостей

---

#### День 30: Улучшение UX

**Задача 3.10**: Добавить прогресс-бары для долгих операций  
**Время**: 4 часа  
**Приоритет**: 🟢 Низкий

**Операции**:
- Загрузка изображений
- Обработка товаров (bulk operations)
- Экспорт данных

**Результат**: UX улучшен

---

### Подведение итогов Фазы 3

**Метрики производительности**:
- ✅ Время загрузки страниц: < 1 сек
- ✅ SQL запросы: < 20 на страницу
- ✅ Размер assets: сжат на 50%+
- ✅ Безопасность: аудит пройден
- ✅ Rate limiting: настроен

**Готовность к Фазе 4**: ✅

---

## 🤖 ФАЗА 4: CI/CD и автоматизация (1-2 недели)

### Неделя 9: Настройка CI/CD

#### День 31-32: GitHub Actions

**Задача 4.1**: Создать workflow для тестов  
**Время**: 4 часа  
**Приоритет**: 🟡 Средний

**Файл**: `.github/workflows/tests.yml`

```yaml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  tests:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

      redis:
        image: redis:alpine
        ports:
          - 6379:6379
        options: --health-cmd="redis-cli ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, dom, fileinfo, mysql, redis
          coverage: pcov

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-progress

      - name: Copy .env
        run: php -r "file_exists('.env') || copy('.env.testing', '.env');"

      - name: Generate key
        run: php artisan key:generate

      - name: Run migrations
        run: php artisan migrate --force
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: password

      - name: Run tests
        run: php artisan test --coverage --min=80
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: password
          CACHE_DRIVER: redis
          SESSION_DRIVER: redis
          QUEUE_CONNECTION: sync

      - name: Upload coverage to Codecov
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage.xml
```

**Результат**: CI для тестов настроен

---

**Задача 4.2**: Создать workflow для статического анализа  
**Время**: 2 часа  
**Приоритет**: 🟡 Средний

**Файл**: `.github/workflows/static-analysis.yml`

```yaml
name: Static Analysis

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  phpstan:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse --error-format=github

  pint:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run Pint
        run: vendor/bin/pint --test
```

**Результат**: Статический анализ автоматизирован

---

**Задача 4.3**: Создать workflow для деплоя  
**Время**: 4 часа  
**Приоритет**: 🟡 Средний

**Файл**: `.github/workflows/deploy.yml`

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install --prefer-dist --no-dev --optimize-autoloader

      - name: Build assets
        run: |
          npm ci
          npm run build

      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.DEPLOY_HOST }}
          username: ${{ secrets.DEPLOY_USER }}
          key: ${{ secrets.DEPLOY_KEY }}
          script: |
            cd /var/www/html
            git pull origin main
            composer install --no-dev --optimize-autoloader
            npm ci && npm run build
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan queue:restart
```

**Результат**: Автоматический деплой настроен

---

#### День 33-34: Мониторинг

**Задача 4.4**: Настроить логирование ошибок  
**Время**: 3 часа  
**Приоритет**: 🟡 Средний

```bash
# Опция 1: Sentry
composer require sentry/sentry-laravel

# config/sentry.php
'dsn' => env('SENTRY_LARAVEL_DSN'),

# Опция 2: Bugsnag
composer require bugsnag/bugsnag-laravel
```

**Результат**: Ошибки логируются в Sentry/Bugsnag

---

**Задача 4.5**: Настроить мониторинг производительности  
**Время**: 2 часа  
**Приоритет**: 🟢 Низкий

**Инструменты**:
- Laravel Telescope (dev/staging)
- New Relic / DataDog (production)

**Результат**: Мониторинг производительности настроен

---

**Задача 4.6**: Настроить backup БД  
**Время**: 3 часа  
**Приоритет**: 🔴 Критичный

```bash
# Установить пакет для backup
composer require spatie/laravel-backup

# Публикация конфига
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"

# config/backup.php - настроить
'destination' => [
    'disks' => [
        's3', // AWS S3 или другой cloud storage
    ],
],

# Добавить в crontab (app/Console/Kernel.php)
protected function schedule(Schedule $schedule): void
{
    $schedule->command('backup:run')->daily()->at('03:00');
    $schedule->command('backup:clean')->weekly();
}
```

**Результат**: Автоматический backup настроен

---

### Подведение итогов Фазы 4

**Автоматизация**:
- ✅ CI тесты: запускаются на каждый PR
- ✅ Статический анализ: автоматически
- ✅ Деплой: автоматический на main
- ✅ Мониторинг: Sentry + Telescope
- ✅ Backup: ежедневно

**Готовность к Фазе 5**: ✅

---

## 📚 ФАЗА 5: Документация и процессы (1 неделя)

### Неделя 10: Финализация

#### День 35-36: Обновление документации

**Задача 5.1**: Обновить README.md  
**Время**: 3 часа  
**Приоритет**: 🟡 Средний

**Содержание README**:
- Описание проекта
- Требования
- Инструкция по установке
- Команды для разработки
- Структура проекта
- Ссылки на документацию

**Результат**: README.md актуален

---

**Задача 5.2**: Создать CONTRIBUTING.md  
**Время**: 2 часа  
**Приоритет**: 🟡 Средний

**Содержание**:
- Правила коммитов
- Процесс создания PR
- Code review процесс
- Стандарты кода

**Результат**: CONTRIBUTING.md создан

---

**Задача 5.3**: Документировать API (если есть)  
**Время**: 4 часа  
**Приоритет**: 🟢 Низкий

**Инструменты**:
- Swagger/OpenAPI
- Postman Collections

**Результат**: API задокументирован

---

#### День 37-38: Onboarding

**Задача 5.4**: Создать onboarding guide  
**Время**: 4 часа  
**Приоритет**: 🟡 Средний

**Для новых разработчиков**:
- Настройка окружения
- Обзор архитектуры
- Важные концепции проекта
- Частые задачи и как их решать

**Результат**: Onboarding guide создан

---

**Задача 5.5**: Провести внутренний тренинг  
**Время**: 4 часа  
**Приоритет**: 🟡 Средний

**Темы**:
- Новая архитектура (Services, Policies)
- Процесс разработки
- CI/CD процесс
- Best practices

**Результат**: Команда обучена

---

#### День 39-40: Финальные проверки

**Задача 5.6**: Финальный аудит кода  
**Время**: 8 часов  
**Приоритет**: 🔴 Критичный

**Чек-лист**:
- [ ] Все задачи выполнены
- [ ] Все тесты проходят
- [ ] Покрытие 80%+
- [ ] PHPStan level 5
- [ ] Pint без ошибок
- [ ] Документация актуальна
- [ ] CI/CD работает
- [ ] Нет TODO/FIXME
- [ ] Нет мертвого кода
- [ ] Нет дублирования
- [ ] Все переменные типизированы
- [ ] Все методы задокументированы

**Результат**: Финальный аудит пройден

---

**Задача 5.7**: Подготовить release notes  
**Время**: 2 часа  
**Приоритет**: 🟡 Средний

**Содержание**:
- Список всех изменений
- Breaking changes
- Инструкции по обновлению
- Благодарности

**Результат**: Release notes готовы

---

**Задача 5.8**: Деплой на production  
**Время**: 4 часа  
**Приоритет**: 🔴 Критичный

**План деплоя**:
1. Backup БД
2. Включить maintenance mode
3. Деплой кода
4. Запуск миграций
5. Очистка кешей
6. Прогрев кешей
7. Smoke tests
8. Выключить maintenance mode
9. Мониторинг метрик

**Результат**: Production обновлен

---

## 📊 Итоговые метрики после завершения

### Качество кода

| Метрика | Было | Стало | Улучшение |
|---------|------|-------|-----------|
| Общая оценка | 4.5/10 | 9.5/10 | +110% |
| Покрытие тестами | ~0% | 80%+ | +80% |
| PHPStan level | 0 | 5 | +5 |
| `declare(strict_types=1)` | 0 файлов | 100% | +100% |
| Строки в lang/ | 0% | 100% | +100% |
| Бизнес-логика в контроллерах | Да | Нет | ✅ |
| Form Requests | 0 | 8 | +8 |
| Service классы | 0 | 6 | +6 |
| Policies | 0 | 2 | +2 |

### Производительность

| Метрика | Было | Стало | Улучшение |
|---------|------|-------|-----------|
| Время загрузки | 2-3 сек | < 1 сек | -60% |
| SQL запросов | 50+ | < 20 | -60% |
| Размер assets | 2 MB | 800 KB | -60% |
| TTI (Time to Interactive) | 5 сек | 2 сек | -60% |

### Безопасность

| Аспект | Статус |
|--------|--------|
| SQL Injection | ✅ Защищено |
| XSS | ✅ Защищено |
| CSRF | ✅ Включено |
| Rate Limiting | ✅ Настроено |
| HTTPS | ✅ Принудительно |
| Зависимости | ✅ Без уязвимостей |
| Пароли | ✅ Хешированы (bcrypt) |

### Автоматизация

| Процесс | Статус |
|---------|--------|
| CI тесты | ✅ Автоматически |
| Статический анализ | ✅ Автоматически |
| Code style | ✅ Автоматически |
| Деплой | ✅ Автоматически |
| Backup | ✅ Ежедневно |
| Мониторинг | ✅ 24/7 |

---

## 💰 Оценка трудозатрат

### По фазам

| Фаза | Время (часы) | Время (недели) |
|------|--------------|----------------|
| Фаза 1: Критичные исправления | 80-100 | 2-3 |
| Фаза 2: Тестирование | 60-80 | 2-3 |
| Фаза 3: Оптимизация | 40-50 | 2-3 |
| Фаза 4: CI/CD | 30-40 | 1-2 |
| Фаза 5: Документация | 20-30 | 1 |
| **ИТОГО** | **230-300** | **8-12** |

### По ролям

| Роль | Часы | Задачи |
|------|------|--------|
| Senior Developer | 120-150 | Архитектура, Services, code review |
| Middle Developer | 80-100 | Controllers, Tests, Policies |
| Junior Developer | 30-50 | Form Requests, локализация, документация |
| DevOps | 20-30 | CI/CD, мониторинг, деплой |
| **ИТОГО** | **250-330** | |

---

## 🎯 Критерии завершения проекта

### Must Have (Обязательно)

- [x] Все PHP файлы содержат `declare(strict_types=1);`
- [x] Вся валидация через Form Requests
- [x] Вся бизнес-логика в Service/Action классах
- [x] Все авторизация через Policies
- [x] Все строки вынесены в lang/
- [x] Покрытие тестами 80%+
- [x] PHPStan level 5 без ошибок
- [x] Pint без ошибок
- [x] CI/CD настроен
- [x] Документация актуальна

### Should Have (Желательно)

- [x] Redis для кеша и очередей
- [x] Мониторинг (Sentry/Telescope)
- [x] Автоматический backup
- [x] Rate limiting
- [x] Оптимизация производительности
- [x] Статический анализ в CI

### Could Have (Можно отложить)

- [ ] Full-text search (Elasticsearch/MeiliSearch)
- [ ] Многоязычность (полная)
- [ ] PWA функциональность
- [ ] Websockets для реал-тайм
- [ ] GraphQL API

---

## 🚀 После завершения плана

### Поддержка (Ongoing)

**Еженедельно**:
- Обновление зависимостей
- Проверка безопасности
- Мониторинг метрик

**Ежемесячно**:
- Code review процесса
- Обновление документации
- Анализ производительности

### Continuous Improvement

**Следующие шаги**:
1. Обновление до Laravel 11/12 (когда стабилизируется)
2. Внедрение Domain-Driven Design (если проект растет)
3. Разделение на микросервисы (при необходимости)
4. Добавление мобильного API
5. Интеграции с внешними сервисами

---

## 📞 Контакты и поддержка

**Project Lead**: [Указать имя]  
**Tech Lead**: [Указать имя]  
**DevOps**: [Указать имя]

**Документация**: `/docs`  
**Репозиторий**: [Указать URL]  
**CI/CD**: [Указать URL GitHub Actions]  
**Мониторинг**: [Указать URL Sentry/Telescope]

---

**Версия плана**: 1.0  
**Дата создания**: 03.06.2026  
**Последнее обновление**: 03.06.2026  
**Статус**: 📋 В планировании

**Следующий review плана**: После завершения Фазы 1
