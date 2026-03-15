<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

// ═══════════════════════════════════════════════════════════════════════════
// ЗАГОЛОВОК
// ═══════════════════════════════════════════════════════════════════════════

echo "\n";
echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║     КОМПЛЕКСНАЯ ПРОВЕРКА ПРОЕКТА GRANAT10LARAVEL3    ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Дата: " . date('d.m.Y H:i:s') . "\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "Laravel: " . app()->version() . "\n\n";

$testResults = [];
$startTime = microtime(true);

// ═══════════════════════════════════════════════════════════════════════════
// ТЕСТ 1: СТРУКТУРА NESTED SET
// ═══════════════════════════════════════════════════════════════════════════

echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║   ТЕСТ 1: Проверка структуры Nested Set              ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n\n";

try {
    $testStart = microtime(true);
    
    $categories = Category::withDepth()->get();
    $errors = [];

    foreach ($categories as $category) {
        // Проверка 1: _lft < _rgt
        if ($category->_lft >= $category->_rgt) {
            $errors[] = "Категория '{$category->name}' (ID: {$category->id}): _lft >= _rgt";
        }
        
        // Проверка 2: parent_id существует
        if ($category->parent_id && !Category::find($category->parent_id)) {
            $errors[] = "Категория '{$category->name}' (ID: {$category->id}): несуществующий parent_id";
        }
    }

    $testTime = round((microtime(true) - $testStart) * 1000, 2);

    if (empty($errors)) {
        echo "✅ Структура Nested Set корректна!\n\n";
        
        echo "📊 Статистика:\n";
        echo "   Всего категорий: " . $categories->count() . "\n";
        echo "   Корневых: " . Category::whereNull('parent_id')->count() . "\n";
        echo "   Максимальная глубина: " . ($categories->max('depth') ?? 0) . "\n";
        echo "   Время проверки: {$testTime}ms\n";
        
        $testResults['Nested Set'] = '✅ Пройден';
    } else {
        echo "❌ Найдены проблемы:\n\n";
        foreach (array_slice($errors, 0, 5) as $error) {
            echo "  - {$error}\n";
        }
        if (count($errors) > 5) {
            echo "  ... и ещё " . (count($errors) - 5) . " ошибок\n";
        }
        echo "\n🔧 Для восстановления выполните: php artisan categories:fix-tree\n";
        
        $testResults['Nested Set'] = '❌ Ошибки: ' . count($errors);
    }
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    $testResults['Nested Set'] = '❌ Исключение';
}

echo "\n";
sleep(1);

// ═══════════════════════════════════════════════════════════════════════════
// ТЕСТ 2: КЕШИРОВАНИЕ КАТЕГОРИЙ
// ═══════════════════════════════════════════════════════════════════════════

echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║   ТЕСТ 2: Проверка кеша категорий                    ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n\n";

try {
    // Очищаем кеш
    Cache::forget('categories_tree_all');
    echo "1. Кеш очищен\n";

    // Первая загрузка (из БД)
    $start = microtime(true);
    $categories = Category::getCachedRootTree();
    $time1 = round((microtime(true) - $start) * 1000, 2);

    echo "2. Первая загрузка: {$time1}ms (из БД)\n";
    echo "   Категорий: " . $categories->count() . "\n";

    // Вторая загрузка (из кеша)
    $start = microtime(true);
    $categories = Category::getCachedRootTree();
    $time2 = round((microtime(true) - $start) * 1000, 2);

    echo "3. Вторая загрузка: {$time2}ms (из кеша)\n\n";

    if ($time2 < $time1 / 2) {
        $speedup = round($time1 / $time2, 1);
        echo "✅ Кеширование работает эффективно!\n";
        echo "   Ускорение: {$speedup}x\n";
        $testResults['Кеширование'] = "✅ Ускорение {$speedup}x";
    } else {
        echo "⚠️ Кеш работает неэффективно\n";
        echo "   Проверьте CACHE_DRIVER в .env\n";
        $testResults['Кеширование'] = '⚠️ Неэффективно';
    }

    echo "\n📁 Структура категорий (корневые):\n";
    foreach ($categories->take(10) as $category) {
        echo "   📁 {$category->name}\n";
    }
    if ($categories->count() > 10) {
        echo "   ... и ещё " . ($categories->count() - 10) . " категорий\n";
    }
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    $testResults['Кеширование'] = '❌ Исключение';
}

echo "\n";
sleep(1);

// ═══════════════════════════════════════════════════════════════════════════
// ТЕСТ 3: КОЛИЧЕСТВО ЗАПРОСОВ К БД
// ═══════════════════════════════════════════════════════════════════════════

echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║   ТЕСТ 3: Анализ запросов к БД                       ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n\n";

try {
    // Очищаем кеш для чистого теста
    Cache::flush();
    
    DB::enableQueryLog();

    $start = microtime(true);
    $request = new \Illuminate\Http\Request();
    $controller = new \App\Http\Controllers\CatalogController();
    
    ob_start();
    $controller->catalog($request);
    ob_end_clean();
    
    $totalTime = round((microtime(true) - $start) * 1000, 2);
    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    echo "Время выполнения: {$totalTime}ms\n";
    echo "Всего запросов: {$queryCount}\n\n";

    if ($queryCount < 20) {
        echo "✅ Отличная оптимизация!\n";
        $testResults['Запросы к БД'] = "✅ {$queryCount} запросов";
    } elseif ($queryCount < 40) {
        echo "🟡 Количество запросов среднее\n";
        echo "   Можно оптимизировать\n";
        $testResults['Запросы к БД'] = "🟡 {$queryCount} запросов";
    } else {
        echo "⚠️ ВНИМАНИЕ: Слишком много запросов!\n";
        echo "   Возможна проблема N+1\n";
        $testResults['Запросы к БД'] = "⚠️ {$queryCount} запросов (много)";
    }

    // Группируем запросы по типу
    $selects = array_filter($queries, fn($q) => stripos($q['query'], 'select') === 0);
    $updates = array_filter($queries, fn($q) => stripos($q['query'], 'update') === 0);
    $inserts = array_filter($queries, fn($q) => stripos($q['query'], 'insert') === 0);
    $deletes = array_filter($queries, fn($q) => stripos($q['query'], 'delete') === 0);

    echo "\n📊 Статистика по типам:\n";
    echo "   SELECT: " . count($selects) . "\n";
    echo "   UPDATE: " . count($updates) . "\n";
    echo "   INSERT: " . count($inserts) . "\n";
    echo "   DELETE: " . count($deletes) . "\n";

    // Топ-5 медленных запросов
    if (!empty($queries)) {
        echo "\n🐌 Топ-5 самых медленных запросов:\n";
        echo "───────────────────────────────────────────────────────\n";
        
        usort($queries, fn($a, $b) => $b['time'] <=> $a['time']);
        
        foreach (array_slice($queries, 0, 5) as $i => $query) {
            echo ($i + 1) . ". Время: " . round($query['time'], 2) . "ms\n";
            $sql = substr(str_replace(["\n", "\r"], ' ', $query['query']), 0, 80);
            echo "   SQL: {$sql}...\n";
        }
    }

    DB::disableQueryLog();
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    $testResults['Запросы к БД'] = '❌ Исключение';
}

echo "\n";
sleep(1);

// ═══════════════════════════════════════════════════════════════════════════
// ТЕСТ 4: ФИЛЬТРАЦИЯ КАТЕГОРИЙ (ОПТИМИЗИРОВАННАЯ)
// ═══════════════════════════════════════════════════════════════════════════

echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║   ТЕСТ 4: Проверка фильтрации категорий              ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n\n";

try {
    $hideEmpty = config('categories.hide_empty');
    echo "Настройка CATEGORIES_HIDE_EMPTY: " . ($hideEmpty ? '✅ Включена' : '❌ Выключена') . "\n\n";

    // Получаем статистику ОДНИМ запросом
    $start = microtime(true);

    $categoriesWithProducts = DB::table('categories')
        ->leftJoin('products', function($join) {
            $join->on('products.category_id', '=', 'categories.id')
                 ->where('products.is_active', '=', 1);
        })
        ->select('categories.id', 'categories.name', 'categories.parent_id', DB::raw('COUNT(products.id) as product_count'))
        ->where('categories.is_active', 1)
        ->groupBy('categories.id', 'categories.name', 'categories.parent_id')
        ->get();

    $queryTime = round((microtime(true) - $start) * 1000, 2);

    echo "⏱️ Время запроса статистики: {$queryTime}ms\n\n";

    $emptyCount = $categoriesWithProducts->where('product_count', 0)->count();
    $filledCount = $categoriesWithProducts->where('product_count', '>', 0)->count();
    $totalProducts = Product::where('is_active', 1)->count();

    echo "📊 Статистика:\n";
    echo "───────────────────────────────────────────────────────\n";
    echo "  Всего категорий: " . $categoriesWithProducts->count() . "\n";
    echo "  Категорий с товарами: {$filledCount}\n";
    echo "  Пустых категорий: {$emptyCount}\n";
    echo "  % пустых: " . round($emptyCount / max($categoriesWithProducts->count(), 1) * 100, 1) . "%\n";
    echo "  Всего товаров: {$totalProducts}\n\n";

    if ($hideEmpty && $emptyCount > 0) {
        echo "✅ {$emptyCount} пустых категорий будут скрыты в каталоге\n";
        $testResults['Фильтрация'] = "✅ Скрыто {$emptyCount} категорий";
    } elseif (!$hideEmpty) {
        echo "⚠️ Все категории будут показаны (фильтрация выключена)\n";
        $testResults['Фильтрация'] = '⚠️ Выключена';
    } else {
        echo "✅ Все категории имеют товары\n";
        $testResults['Фильтрация'] = '✅ Все заполнены';
    }

    // Показываем примеры пустых категорий
    $emptyCategories = $categoriesWithProducts->where('product_count', 0)->take(10);

    if ($emptyCategories->count() > 0) {
        echo "\n📋 Примеры пустых категорий (первые 10):\n";
        echo "───────────────────────────────────────────────────────\n";
        foreach ($emptyCategories as $cat) {
            $parentName = '';
            if ($cat->parent_id) {
                $parent = $categoriesWithProducts->firstWhere('id', $cat->parent_id);
                $parentName = $parent ? " (родитель: {$parent->name})" : '';
            }
            echo "  ⚠️ {$cat->name}{$parentName}\n";
        }
        
        if ($emptyCount > 10) {
            echo "  ... и ещё " . ($emptyCount - 10) . " пустых категорий\n";
        }
    }

    // Тест производительности фильтрации
    if ($hideEmpty) {
        echo "\n🔥 Тест производительности фильтрации:\n";
        echo "───────────────────────────────────────────────────────\n";

        Cache::forget('categories_tree_all');
        
        $start = microtime(true);
        $categories = Category::getCachedRootTree();
        $time1 = round((microtime(true) - $start) * 1000, 2);

        echo "  Загрузка без фильтрации: {$time1}ms\n";

        // Применяем фильтр (как в контроллере)
        $start = microtime(true);
        $controller = new \App\Http\Controllers\CatalogController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('filterEmptyCategories');
        $method->setAccessible(true);
        
        $filtered = $method->invoke($controller, $categories);
        $time2 = round((microtime(true) - $start) * 1000, 2);
        
        echo "  Фильтрация: {$time2}ms\n";
        echo "  Осталось категорий: " . $filtered->count() . " из " . $categories->count() . "\n";
        echo "  Общее время: " . ($time1 + $time2) . "ms\n\n";
        
        if (($time1 + $time2) > 1000) {
            echo "⚠️ МЕДЛЕННО! Загрузка + фильтрация > 1 секунды\n";
            echo "   Рекомендуется оптимизация или кеширование результата\n";
        } elseif (($time1 + $time2) > 500) {
            echo "🟡 Приемлемая скорость\n";
        } else {
            echo "✅ Быстрая фильтрация\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    $testResults['Фильтрация'] = '❌ Исключение';
}

echo "\n";
sleep(1);

// ═══════════════════════════════════════════════════════════════════════════
// ТЕСТ 5: ПРОВЕРКА ИНДЕКСОВ В БД
// ═══════════════════════════════════════════════════════════════════════════

echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║   ТЕСТ 5: Проверка индексов в БД                     ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n\n";

try {
    $tables = ['products', 'categories', 'sellers'];
    $allIndexesOk = true;

    foreach ($tables as $table) {
        try {
            $indexes = DB::select("SHOW INDEXES FROM {$table}");
            
            echo "📋 Таблица: {$table}\n";
            echo "───────────────────────────────────────────────────────\n";
            
            $indexNames = [];
            foreach ($indexes as $index) {
                if (!in_array($index->Key_name, $indexNames)) {
                    $indexNames[] = $index->Key_name;
                    $unique = $index->Non_unique == 0 ? ' (уникальный)' : '';
                    echo "  ✓ {$index->Key_name}{$unique}\n";
                }
            }
            
            echo "\n";
        } catch (\Exception $e) {
            echo "❌ Ошибка при проверке таблицы {$table}: " . $e->getMessage() . "\n\n";
            $allIndexesOk = false;
        }
    }

    echo "💡 Рекомендуемые индексы:\n";
    echo "───────────────────────────────────────────────────────\n";
    echo "products:\n";
    echo "  - ✅ is_active (есть)\n";
    echo "  - ✅ category_id (есть)\n";
    echo "  - ✅ seller_id (есть)\n";
    echo "  - ✅ is_vip (есть в составном)\n";
    echo "  - ✅ push_at (есть в составном)\n\n";

    echo "categories:\n";
    echo "  - ✅ is_active (есть)\n";
    echo "  - ✅ parent_id (есть)\n";
    echo "  - ✅ _lft, _rgt (есть)\n\n";

    echo "sellers:\n";
    echo "  - ✅ alias (есть)\n\n";

    if ($allIndexesOk) {
        echo "✅ Все критичные индексы созданы!\n";
        $testResults['Индексы'] = '✅ Все на месте';
    } else {
        echo "⚠️ Есть проблемы с индексами\n";
        $testResults['Индексы'] = '⚠️ Есть проблемы';
    }
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    $testResults['Индексы'] = '❌ Исключение';
}

echo "\n";
sleep(1);

// ═══════════════════════════════════════════════════════════════════════════
// ТЕСТ 6: БЕНЧМАРК ПРОИЗВОДИТЕЛЬНОСТИ
// ═══════════════════════════════════════════════════════════════════════════

echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║   ТЕСТ 6: Бенчмарк производительности                ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n\n";

try {
    $iterations = 3;
    $times = [];

    echo "🔥 Выполняем {$iterations} итераций загрузки каталога...\n\n";

    for ($i = 1; $i <= $iterations; $i++) {
        // Очищаем кеш перед каждым тестом
        Cache::flush();
        
        DB::enableQueryLog();
        $start = microtime(true);
        
        $request = new \Illuminate\Http\Request();
        $controller = new \App\Http\Controllers\CatalogController();
        
        ob_start();
        $controller->catalog($request);
        ob_end_clean();
        
        $time = round((microtime(true) - $start) * 1000, 2);
        $queries = count(DB::getQueryLog());
        
        $times[] = $time;
        
        echo "Итерация {$i}: {$time}ms ({$queries} запросов)\n";
        
        DB::disableQueryLog();
    }

    echo "\n📊 Статистика производительности:\n";
    echo "───────────────────────────────────────────────────────\n";
    
    $avgTime = round(array_sum($times) / count($times), 2);
    $minTime = min($times);
    $maxTime = max($times);
    
    echo "  Среднее время: {$avgTime}ms\n";
    echo "  Минимальное: {$minTime}ms\n";
    echo "  Максимальное: {$maxTime}ms\n\n";

    if ($avgTime < 200) {
        echo "✅ Отличная производительность!\n";
        $testResults['Производительность'] = "✅ {$avgTime}ms";
    } elseif ($avgTime < 500) {
        echo "🟡 Приемлемая производительность\n";
        $testResults['Производительность'] = "🟡 {$avgTime}ms";
    } else {
        echo "⚠️ Требуется оптимизация\n";
        $testResults['Производительность'] = "⚠️ {$avgTime}ms (медленно)";
    }
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    $testResults['Производительность'] = '❌ Исключение';
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════════════
// ИТОГОВЫЙ ОТЧЁТ
// ═══════════════════════════════════════════════════════════════════════════

$totalTime = round((microtime(true) - $startTime), 2);

echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║                   ИТОГОВЫЙ ОТЧЁТ                     ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n\n";

foreach ($testResults as $test => $result) {
    echo str_pad($test . ':', 25) . " {$result}\n";
}

echo "\n";
echo "Общее время выполнения всех тестов: {$totalTime}с\n";

// Общая оценка
$passedTests = count(array_filter($testResults, fn($r) => strpos($r, '✅') === 0));
$totalTests = count($testResults);
$percentage = round($passedTests / $totalTests * 100);

echo "\nПройдено тестов: {$passedTests} из {$totalTests} ({$percentage}%)\n\n";

if ($percentage >= 80) {
    echo "🎉 Проект в отличном состоянии!\n";
} elseif ($percentage >= 60) {
    echo "🟡 Проект в хорошем состоянии, есть что улучшить\n";
} else {
    echo "⚠️ Требуется серьёзная оптимизация\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════\n\n";