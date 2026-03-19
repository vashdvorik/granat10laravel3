<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Тесты производительности: HIDE_EMPTY=false vs true
 *
 * Запуск:
 *   php artisan test tests/Feature/CatalogPerformanceTest.php --testdox
 */
class CatalogPerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // =========================================================================
    // ТЕСТ 1: Тёплый кеш — сколько SQL делает каждый запрос к /catalog
    // =========================================================================

    public function test_warm_cache_requests_hide_empty_false_vs_true(): void
    {
        // ── HIDE_EMPTY = false ────────────────────────────────────────────────
        Cache::flush();
        Config::set('categories.hide_empty', false);

        // Запрос 1: прогрев кеша
        $this->get('/catalog');

        // Запросы 2-4: тёплый кеш
        $resultsOff = [];
        for ($i = 1; $i <= 4; $i++) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $start = microtime(true);
            $this->get('/catalog');
            $ms = (microtime(true) - $start) * 1000;
            $sql = count(DB::getQueryLog());
            DB::disableQueryLog();
            $resultsOff[] = ['sql' => $sql, 'ms' => round($ms, 1)];
        }

        // ── HIDE_EMPTY = true ─────────────────────────────────────────────────
        Cache::flush();
        Config::set('categories.hide_empty', true);

        // Запрос 1: прогрев кеша
        $this->get('/catalog');

        // Запросы 2-4: тёплый кеш
        $resultsOn = [];
        for ($i = 1; $i <= 4; $i++) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $start = microtime(true);
            $this->get('/catalog');
            $ms = (microtime(true) - $start) * 1000;
            $sql = count(DB::getQueryLog());
            DB::disableQueryLog();
            $resultsOn[] = ['sql' => $sql, 'ms' => round($ms, 1)];
        }

        $avgSqlOff = round(array_sum(array_column($resultsOff, 'sql')) / count($resultsOff), 1);
        $avgMsOff  = round(array_sum(array_column($resultsOff, 'ms'))  / count($resultsOff), 1);
        $avgSqlOn  = round(array_sum(array_column($resultsOn,  'sql')) / count($resultsOn),  1);
        $avgMsOn   = round(array_sum(array_column($resultsOn,  'ms'))  / count($resultsOn),  1);

        echo "\n";
        echo "\n╔══════════════════════════════════════════════════════════════════╗";
        echo "\n║  ТЕСТ 1: GET /catalog — тёплый кеш, 4 повторных запроса         ║";
        echo "\n╚══════════════════════════════════════════════════════════════════╝";
        echo "\n";
        echo "\n  HIDE_EMPTY=false:";
        foreach ($resultsOff as $i => $r) {
            echo "\n    Запрос #" . ($i + 1) . ": {$r['sql']} SQL,  {$r['ms']} мс";
        }
        echo "\n  → Среднее: {$avgSqlOff} SQL,  {$avgMsOff} мс";
        echo "\n";
        echo "\n  HIDE_EMPTY=true:";
        foreach ($resultsOn as $i => $r) {
            echo "\n    Запрос #" . ($i + 1) . ": {$r['sql']} SQL,  {$r['ms']} мс";
        }
        echo "\n  → Среднее: {$avgSqlOn} SQL,  {$avgMsOn} мс";
        echo "\n";
        echo "\n  Разница на тёплом кеше:";
        echo "\n    SQL:   +" . ($avgSqlOn - $avgSqlOff);
        echo "\n    Время: +" . round($avgMsOn - $avgMsOff, 1) . " мс";
        echo "\n";

        if ($avgSqlOn > $avgSqlOff + 5) {
            echo "\n  ПРОБЛЕМА: При HIDE_EMPTY=true ДАЖЕ НА ТЁПЛОМ КЕШЕ";
            echo "\n     выполняется на " . round($avgSqlOn - $avgSqlOff) . " SQL-запросов больше.";
            echo "\n     Причина: CatalogController::filterEmptyCategories() НЕ кешируется";
            echo "\n     и вызывается при каждом HTTP-запросе отдельно от AppServiceProvider.";
        } else {
            echo "\n  OK: На тёплом кеше разница в SQL минимальна.";
        }
        echo "\n";

        $this->assertNotEmpty($resultsOff);
        $this->assertNotEmpty($resultsOn);
    }

    // =========================================================================
    // ТЕСТ 2: Детальный разбор SQL на тёплом кеше при HIDE_EMPTY=true
    //         Показывает ОТКУДА берутся лишние запросы
    // =========================================================================

    public function test_warm_cache_sql_breakdown_hide_empty_true(): void
    {
        Config::set('categories.hide_empty', true);
        Cache::flush();

        // Прогрев
        $this->get('/catalog');

        // Один замер с полным логом
        DB::flushQueryLog();
        DB::enableQueryLog();
        $start = microtime(true);
        $this->get('/catalog');
        $totalMs = (microtime(true) - $start) * 1000;
        $allQueries = DB::getQueryLog();
        DB::disableQueryLog();

        // Классифицируем запросы
        $groups = [
            'filter: descendants (_lft)'    => [],
            'filter: exists(products)'      => [],
            'catalog: products count/cache' => [],
            'catalog: products paginate'    => [],
            'other'                         => [],
        ];

        foreach ($allQueries as $q) {
            $sql = strtolower($q['query']);
            if (str_contains($sql, '_lft') && str_contains($sql, 'categor')) {
                $groups['filter: descendants (_lft)'][] = $q;
            } elseif (str_contains($sql, 'exists') && str_contains($sql, 'product')) {
                $groups['filter: exists(products)'][] = $q;
            } elseif (str_contains($sql, 'count(*)') && str_contains($sql, 'product')) {
                $groups['catalog: products count/cache'][] = $q;
            } elseif (str_contains($sql, 'from `products`') || str_contains($sql, "from \"products\"")) {
                $groups['catalog: products paginate'][] = $q;
            } else {
                $groups['other'][] = $q;
            }
        }

        echo "\n";
        echo "\n╔══════════════════════════════════════════════════════════════════╗";
        echo "\n║  ТЕСТ 2: Детальный SQL на тёплом кеше — HIDE_EMPTY=true          ║";
        echo "\n╚══════════════════════════════════════════════════════════════════╝";
        echo "\n  Итого SQL: " . count($allQueries) . "   Время ответа: " . round($totalMs, 1) . " мс";
        echo "\n";

        $filterSql = 0;
        foreach ($groups as $label => $items) {
            $cnt     = count($items);
            $timeSum = round(array_sum(array_column($items, 'time')), 2);
            $bar     = str_repeat('|', min($cnt, 60));
            echo "\n  [" . str_pad($cnt, 3, ' ', STR_PAD_LEFT) . " SQL / " . str_pad($timeSum, 6, ' ', STR_PAD_LEFT) . " мс]  {$label}  {$bar}";
            if (str_contains($label, 'filter:')) {
                $filterSql += $cnt;
            }
        }

        echo "\n";
        echo "\n  ── filterEmptyCategories() итого: {$filterSql} SQL ──";
        echo "\n";

        if ($filterSql > 10) {
            echo "\n  ИСТОЧНИК ТОРМОЖЕНИЯ: {$filterSql} из " . count($allQueries) . " SQL — это";
            echo "\n  CatalogController::filterEmptyCategories() (~строка 72 контроллера).";
            echo "\n  Метод обходит каждую категорию отдельными запросами (N+1) и";
            echo "\n  НЕ использует кеш 'menu_categories' из AppServiceProvider.";
            echo "\n";
            echo "\n  Вызов в контроллере (catalog()):";
            echo "\n    \$categories = \$this->filterEmptyCategories(\$categories);  // <-- HERE";
            echo "\n";
            echo "\n  Решение: заменить на";
            echo "\n    \$categories = Cache::remember('menu_categories', now()->addHours(24),";
            echo "\n        fn () => Category::filterTreeByProducts(\$categories));";
        } else {
            echo "\n  OK: filterEmptyCategories даёт мало запросов. Торможение в другом.";
        }
        echo "\n";

        $this->assertNotEmpty($allQueries);
    }

    // =========================================================================
    // ТЕСТ 3: Прямое сравнение — cold/warm для обоих режимов + прогноз
    // =========================================================================

    public function test_cold_and_warm_comparison_summary(): void
    {
        $scenarios = [
            ['label' => 'HIDE_EMPTY=false (холодный)', 'hide' => false, 'warm' => false],
            ['label' => 'HIDE_EMPTY=false (тёплый)  ', 'hide' => false, 'warm' => true],
            ['label' => 'HIDE_EMPTY=true  (холодный)', 'hide' => true,  'warm' => false],
            ['label' => 'HIDE_EMPTY=true  (тёплый)  ', 'hide' => true,  'warm' => true],
        ];

        $measurements = [];

        foreach ($scenarios as $s) {
            Cache::flush();
            Config::set('categories.hide_empty', $s['hide']);

            if ($s['warm']) {
                $this->get('/catalog'); // прогрев
            }

            // 3 замера — берём медиану
            $times = [];
            $sqls  = [];
            for ($i = 0; $i < 3; $i++) {
                DB::flushQueryLog();
                DB::enableQueryLog();
                $start = microtime(true);
                $this->get('/catalog');
                $times[] = (microtime(true) - $start) * 1000;
                $sqls[]  = count(DB::getQueryLog());
                DB::disableQueryLog();
            }
            sort($times);
            sort($sqls);

            $measurements[] = [
                'label' => $s['label'],
                'sql'   => $sqls[1],
                'ms'    => round($times[1], 1),
            ];
        }

        echo "\n";
        echo "\n╔══════════════════════════════════════════════════════════════════╗";
        echo "\n║  ТЕСТ 3: Сводная таблица — cold/warm x HIDE_EMPTY               ║";
        echo "\n╚══════════════════════════════════════════════════════════════════╝";
        echo "\n";
        echo "\n  Сценарий                           SQL (медиана)   Время (медиана)";
        echo "\n  ───────────────────────────────────────────────────────────────────";
        foreach ($measurements as $m) {
            printf("\n  %-33s    %5d SQL     %7.1f мс", $m['label'], $m['sql'], $m['ms']);
        }
        echo "\n";

        $warmFalse = $measurements[1];
        $warmTrue  = $measurements[3];
        $deltaSql  = $warmTrue['sql'] - $warmFalse['sql'];
        $deltaMs   = round($warmTrue['ms'] - $warmFalse['ms'], 1);

        echo "\n";
        echo "\n  Накладные расходы HIDE_EMPTY=true vs false (тёплый кеш):";
        printf("\n    +%d SQL,  +%.1f мс", $deltaSql, $deltaMs);
        echo "\n";

        if ($deltaSql > 0) {
            echo "\n  ПРОБЛЕМА: На тёплом кеше HIDE_EMPTY=true добавляет {$deltaSql} SQL /запрос.";
            echo "\n  CatalogController::filterEmptyCategories() работает мимо кеша.";
        } else {
            echo "\n  OK: На тёплом кеше разница практически отсутствует.";
        }
        echo "\n";

        $this->assertNotEmpty($measurements);
    }
}
