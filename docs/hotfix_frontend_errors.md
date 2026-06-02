# Исправление критических ошибок на фронтенде

**Дата**: 3 июня 2026, 02:50  
**Тип работы**: Hotfix - критические ошибки production  

---

## 🐛 Обнаруженные ошибки

### 1. `Method CatalogController::sale does not exist`
**Причина**: Маршруты требовали методы, которые отсутствовали в контроллере  
**Затронутые маршруты**:
- `/sale` → `sale()`
- `/sale/load-more` → `loadMoreSale()`
- `/catalog/load-more` → `loadMoreCatalog()`
- `/catalog/{alias}/load-more` → `loadMoreCategory()`
- `/search/load-more` → `loadMoreSearch()`

### 2. `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'slug'`
**Причина**: CategoryService использовал поле `slug`, которого нет в таблице  
**Локация**: `CategoryService::getCategoryBySlug()`  
**Запрос**: `SELECT * FROM categories WHERE slug = 'home-appliances'`

### 3. `View [catalog.search] not found`
**Причина**: Отсутствовал view-файл для страницы поиска

### 4. `View [catalog.sale] not found`
**Причина**: Отсутствовал view-файл для страницы распродажи

### 5. `strlen(): Argument #1 ($string) must be of type string, null given`
**Причина**: Передача null в strlen() без предварительной проверки  
**Локации**:
- `CatalogController::search()` - проверка длины поискового запроса
- `CatalogController::loadMoreSearch()` - проверка длины запроса в AJAX
- `CatalogController::show()` - обработка $category->description

---

## ✅ Выполненные исправления

### 1. Добавлены недостающие методы в CatalogController

**Файл**: `app/Http/Controllers/CatalogController.php`

#### `sale()` - страница распродажи
```php
public function sale(Request $request)
{
    $filters = ['on_sale' => true];
    $priceRange = $this->getCachedPriceRange();
    $filters = array_merge($filters, $this->prepareFilters($request, $priceRange));
    $products = $this->productService->getVisibleProducts($filters, 24);
    $categories = $this->categoryService->getRootCategories();
    
    return view('catalog.sale', compact('products', 'categories'));
}
```

#### `loadMoreCatalog()` - AJAX подгрузка товаров каталога
```php
public function loadMoreCatalog(Request $request)
{
    $page = $request->input('page', 1);
    // ... фильтры и пагинация
    return response()->json([
        'html' => view('catalog.partials.products', compact('products'))->render(),
        'hasMore' => $products->hasMorePages(),
    ]);
}
```

#### `loadMoreCategory()` - AJAX подгрузка товаров категории
```php
public function loadMoreCategory(string $alias, Request $request)
{
    $category = $this->categoryService->getCategoryBySlug($alias);
    // ... получение товаров категории
    return response()->json([...]);
}
```

#### `loadMoreSale()` - AJAX подгрузка товаров распродажи
```php
public function loadMoreSale(Request $request)
{
    $filters = ['on_sale' => true];
    // ... получение товаров со скидкой
    return response()->json([...]);
}
```

#### `loadMoreSearch()` - AJAX подгрузка результатов поиска
```php
public function loadMoreSearch(Request $request)
{
    $searchQuery = $request->input('q', '');
    // ... поиск и пагинация
    return response()->json([...]);
}
```

**Всего добавлено**: 5 методов, ~110 строк кода

---

### 2. Исправлено использование поля `slug` → `alias`

**Файл**: `app/Services/CategoryService.php`

**Было**:
```php
public function getCategoryBySlug(string $slug): ?Category
{
    return Cache::remember(
        "category.slug.{$slug}",
        now()->addHours(24),
        fn () => Category::where('slug', $slug)->first()
    );
}
```

**Стало**:
```php
public function getCategoryBySlug(string $slug): ?Category
{
    return Cache::remember(
        "category.slug.{$slug}",
        now()->addHours(24),
        fn () => Category::where('alias', $slug)->first()
    );
}
```

**Результат**: SQL-запрос теперь использует существующее поле `alias`

---

### 3. Добавлен фильтр `on_sale` в ProductService

**Файл**: `app/Services/ProductService.php`

**Изменения**:
1. Добавлен параметр `$page` в метод `getVisibleProducts()`
2. Добавлен фильтр для товаров со скидкой

```php
public function getVisibleProducts(array $filters = [], int $perPage = 20, ?int $page = null): LengthAwarePaginator
{
    // ...
    
    // Фильтр по распродаже (товары со скидкой)
    if (! empty($filters['on_sale'])) {
        $query->whereNotNull('lowprice')
              ->where('lowprice', '>', 0)
              ->whereRaw('lowprice < price');
    }
    
    // Пагинация с учетом переданной страницы
    if ($page !== null) {
        return $query->paginate($perPage, ['*'], 'page', $page);
    }
    
    return $query->paginate($perPage);
}
```

**Логика фильтра**: товар считается со скидкой, если:
- `lowprice IS NOT NULL`
- `lowprice > 0`
- `lowprice < price`

---

### 4. Исправлены ошибки `strlen()` с null

**Файл**: `app/Http/Controllers/CatalogController.php`

#### Метод `search()`
**Было**:
```php
if (strlen($searchQuery) < 3) {
```

**Стало**:
```php
if (empty($searchQuery) || strlen($searchQuery) < 3) {
```

#### Метод `loadMoreSearch()`
**Было**:
```php
if (strlen($searchQuery) < 3) {
```

**Стало**:
```php
if (empty($searchQuery) || strlen($searchQuery) < 3) {
```

#### Метод `show()` - обработка description
**Было**:
```php
$metaDescription = $category->meta_description ?: $category->description;
```

**Стало**:
```php
$metaDescription = $category->meta_description ?: ($category->description ?? '');
```

**Результат**: предотвращены ошибки при передаче null в strlen()

---

### 5. Созданы недостающие view-файлы

#### `resources/views/catalog/search.blade.php`
- Страница результатов поиска
- Отображение списка найденных товаров
- AJAX подгрузка через кнопку "Загрузить еще"
- Фильтр по категориям

**Функционал**:
- Хлебные крошки
- Sidebar с категориями
- Список товаров через partial
- AJAX пагинация
- Сообщение при пустых результатах

#### `resources/views/catalog/sale.blade.php`
- Страница распродажи (товары со скидкой)
- Аналогичная структура поиску
- AJAX подгрузка через `catalog.loadMoreSale`

#### `resources/views/catalog/partials/products.blade.php`
- Партиал для вывода списка товаров
- Используется всеми AJAX-методами
- Переиспользование существующего компонента `productcategories`

```blade
@foreach($products as $product)
    @include('components.productcategories', ['product' => $product])
@endforeach
```

**Размер**: 3 файла, ~150 строк кода

---

### 6. Очистка кэша

**Команды выполнены**:
```bash
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

**Результат**: Все изменения применены без необходимости перезапуска сервера

---

## 📊 Статистика изменений

| Тип изменения | Файлов | Строк кода |
|---------------|--------|------------|
| **Контроллеры** | 1 | +120 |
| **Сервисы** | 2 | +15 |
| **Views** | 3 | +150 |
| **ИТОГО** | **6** | **~285** |

---

## 🧪 Тестирование

### Проверенные страницы:
- ✅ `/catalog` - главная каталога
- ✅ `/catalog/{alias}` - страница категории
- ✅ `/sale` - распродажа
- ✅ `/search?q=запрос` - поиск

### Проверенные функции:
- ✅ AJAX подгрузка товаров в каталоге
- ✅ AJAX подгрузка товаров в категории
- ✅ AJAX подгрузка товаров в распродаже
- ✅ AJAX подгрузка результатов поиска
- ✅ Фильтр товаров со скидкой
- ✅ Обработка пустых/null значений

---

## ⚠️ Важные замечания

### 1. Поле `slug` vs `alias`
В проекте используется поле **`alias`** для URL категорий, а не `slug`.  
Это нужно учитывать при создании новых миграций или изменении существующих.

### 2. Фильтр `on_sale`
Фильтр основан на наличии поля `lowprice` в таблице products:
- Если `lowprice` не заполнен → товар не в распродаже
- Если `lowprice >= price` → логика некорректна (скидка отсутствует)

**Рекомендация**: Добавить валидацию на уровне Form Request, чтобы `lowprice` всегда был меньше `price`.

### 3. View файлы
Созданные views используют существующий layout `main_layots` (с опечаткой).  
**Рекомендация**: Переименовать layout в `main_layouts` в будущем.

### 4. Пагинация AJAX
Все AJAX-методы возвращают HTML + флаг `hasMore`.  
Frontend должен обрабатывать эти данные и скрывать кнопку "Загрузить еще" когда `hasMore = false`.

---

## 🔄 Следующие шаги

### Краткосрочные (не критично):
1. Добавить валидацию `lowprice < price` в Form Requests
2. Проверить и исправить опечатку в layout (`main_layots` → `main_layouts`)
3. Добавить тесты для новых методов контроллера

### Долгосрочные:
1. Создать миграцию для добавления индексов на поля:
   - `categories.alias` (используется часто)
   - `products.lowprice` (используется в фильтре on_sale)
2. Рассмотреть добавление computed column для флага "в распродаже"
3. Оптимизировать N+1 запросы в партиалах products

---

## ✅ Результат

Все критические ошибки **исправлены**:
- ✅ Страницы доступны без ошибок 500
- ✅ SQL-запросы используют правильные поля
- ✅ AJAX пагинация работает корректно
- ✅ Нет ошибок типизации (strlen с null)

**Сайт полностью функционален** 🎉

---

**Автор**: Senior Developer (AI Agent)  
**Версия**: 1.0  
**Создано**: 3 июня 2026, 02:50
