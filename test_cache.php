<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Cache;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;

echo "=== ТЕСТИРОВАНИЕ КЕШИРОВАНИЯ ===\n\n";

// Очистим кеш перед тестами
Cache::flush();
echo "✓ Кеш полностью очищен\n\n";

// ========================================
// ТЕСТ 1: Кеширование категорий
// ========================================
echo "--- ТЕСТ 1: Категории ---\n";

// Проверка отсутствия кеша
if (!Cache::has('categories_root_tree')) {
    echo "✓ Кеш categories_root_tree отсутствует (ожидается)\n";
} else {
    echo "✗ ОШИБКА: Кеш уже существует!\n";
}

// Первый запрос - должен создать кеш
$start = microtime(true);
$tree = Category::getCachedRootTree();
$time1 = round((microtime(true) - $start) * 1000, 2);
echo "✓ Первый запрос getCachedRootTree(): {$time1}ms (с БД)\n";

// Проверка создания кеша
if (Cache::has('categories_root_tree')) {
    echo "✓ Кеш categories_root_tree создан\n";
} else {
    echo "✗ ОШИБКА: Кеш не создался!\n";
}

// Второй запрос - должен взять из кеша
$start = microtime(true);
$tree2 = Category::getCachedRootTree();
$time2 = round((microtime(true) - $start) * 1000, 2);
echo "✓ Второй запрос getCachedRootTree(): {$time2}ms (из кеша)\n";

if ($time2 < $time1) {
    echo "✓ УСПЕХ: Второй запрос быстрее первого (кеш работает!)\n";
} else {
    echo "⚠ ВНИМАНИЕ: Второй запрос не быстрее\n";
}

echo "\n";

// ========================================
// ТЕСТ 2: Кеширование продавцов
// ========================================
echo "--- ТЕСТ 2: Продавцы ---\n";

// Очистим кеш продавцов
Cache::forget('sellers_list');

// Первый запрос
$start = microtime(true);
$sellers = Seller::getCachedAll();
$time1 = round((microtime(true) - $start) * 1000, 2);
$count = $sellers->count();
echo "✓ Первый запрос getCachedAll(): {$time1}ms, найдено продавцов: {$count}\n";

// Второй запрос из кеша
$start = microtime(true);
$sellers2 = Seller::getCachedAll();
$time2 = round((microtime(true) - $start) * 1000, 2);
echo "✓ Второй запрос getCachedAll(): {$time2}ms (из кеша)\n";

if ($time2 < $time1) {
    echo "✓ УСПЕХ: Кеш продавцов работает!\n";
} else {
    echo "⚠ ВНИМАНИЕ: Кеш может не работать\n";
}

// Тест getCachedByAlias
if ($count > 0) {
    $firstSeller = $sellers->first();
    $alias = $firstSeller->alias;
    
    echo "\nТест getCachedByAlias для alias: {$alias}\n";
    
    Cache::forget('seller_by_alias:' . $alias);
    
    $start = microtime(true);
    $seller = Seller::getCachedByAlias($alias);
    $time1 = round((microtime(true) - $start) * 1000, 2);
    echo "✓ Первый запрос getCachedByAlias(): {$time1}ms\n";
    
    $start = microtime(true);
    $seller2 = Seller::getCachedByAlias($alias);
    $time2 = round((microtime(true) - $start) * 1000, 2);
    echo "✓ Второй запрос getCachedByAlias(): {$time2}ms\n";
    
    if ($seller && $seller2 && $seller->id === $seller2->id) {
        echo "✓ УСПЕХ: Кеш по alias работает корректно!\n";
    }
}

echo "\n";

// ========================================
// ТЕСТ 3: Автоматическая очистка кеша
// ========================================
echo "--- ТЕСТ 3: Автоматическая очистка кеша ---\n";

// Создадим кеш категорий
Category::getCachedRootTree();
echo "✓ Кеш категорий создан\n";

// Проверим наличие
if (Cache::has('categories_root_tree')) {
    echo "✓ Кеш categories_root_tree существует\n";
} else {
    echo "✗ ОШИБКА: Кеш не создался!\n";
}

// Создадим или обновим категорию
$testCategory = Category::first();
if ($testCategory) {
    $oldName = $testCategory->name;
    $testCategory->name = $oldName . ' (тест)';
    $testCategory->save();
    
    echo "✓ Категория обновлена (ID: {$testCategory->id})\n";
    
    // Проверим, что кеш очистился
    if (!Cache::has('categories_root_tree')) {
        echo "✓ УСПЕХ: Кеш автоматически очистился после обновления!\n";
    } else {
        echo "✗ ОШИБКА: Кеш не очистился автоматически!\n";
    }
    
    // Вернём имя обратно
    $testCategory->name = $oldName;
    $testCategory->save();
    echo "✓ Категория восстановлена\n";
} else {
    echo "⚠ ПРОПУЩЕНО: Нет категорий для теста\n";
}

echo "\n";

// ========================================
// ТЕСТ 4: Кеш количества товаров в категории
// ========================================
echo "--- ТЕСТ 4: Количество товаров в категории ---\n";

$category = Category::whereHas('products')->first();
if ($category) {
    $cacheKey = 'category_products_count:' . $category->id;
    Cache::forget($cacheKey);
    
    echo "Тестируем категорию ID: {$category->id}\n";
    
    $start = microtime(true);
    $count1 = Product::getCategoryProductsCount($category->id);
    $time1 = round((microtime(true) - $start) * 1000, 2);
    echo "✓ Первый запрос getCategoryProductsCount(): {$time1}ms, товаров: {$count1}\n";
    
    $start = microtime(true);
    $count2 = Product::getCategoryProductsCount($category->id);
    $time2 = round((microtime(true) - $start) * 1000, 2);
    echo "✓ Второй запрос getCategoryProductsCount(): {$time2}ms, товаров: {$count2}\n";
    
    if ($count1 === $count2 && $time2 < $time1) {
        echo "✓ УСПЕХ: Кеш подсчета товаров работает!\n";
    }
} else {
    echo "⚠ ПРОПУЩЕНО: Нет категорий с товарами\n";
}

echo "\n";

// ========================================
// ТЕСТ 5: Кеш диапазона цен
// ========================================
echo "--- ТЕСТ 5: Диапазон цен товаров ---\n";

Cache::forget('product_price_range');

$start = microtime(true);
$range1 = Product::getCachedPriceRange();
$time1 = round((microtime(true) - $start) * 1000, 2);
echo "✓ Первый запрос getCachedPriceRange(): {$time1}ms\n";
echo "  Мин: {$range1['min']}, Макс: {$range1['max']}\n";

$start = microtime(true);
$range2 = Product::getCachedPriceRange();
$time2 = round((microtime(true) - $start) * 1000, 2);
echo "✓ Второй запрос getCachedPriceRange(): {$time2}ms\n";

if ($range1 === $range2 && $time2 < $time1) {
    echo "✓ УСПЕХ: Кеш диапазона цен работает!\n";
}

echo "\n";

// ========================================
// ИТОГИ
// ========================================
echo "=== ТЕСТИРОВАНИЕ ЗАВЕРШЕНО ===\n";
echo "\nПроверьте результаты выше.\n";
echo "Знак ✓ означает успешный тест\n";
echo "Знак ✗ означает ошибку\n";
echo "Знак ⚠ означает предупреждение или пропущенный тест\n";
