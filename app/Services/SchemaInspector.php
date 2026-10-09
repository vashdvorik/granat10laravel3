<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * «Есть ли такая таблица» без запросов к базе на каждой странице.
 *
 * `Schema::hasTable()` на MySQL выбирает список таблиц из information_schema (с размерами), и это занимает
 * несколько миллисекунд на каждый вызов. Раньше проверка выполнялась на каждой странице 4-8 раз (настройки дизайна,
 * регионы, категории ...). Теперь: положительный ответ запоминается на сутки в кеше (таблицы не пропадают,
 * кеш сбрасывается при `optimize:clear`), а внутри запроса ответ запоминается в памяти.
 * Отрицательный ответ не запоминается нигде (ни в кеше, ни в памяти): пока таблицы нет (миграции ещё не выполнены),
 * проверка идёт каждый раз, а появившаяся таблица (например, после `migrate` в том же процессе) сразу видна.
 *
 * Регистрируется как scoped-сервис: в каждом запросе (и в каждом тесте) начинает с чистого листа.
 */
class SchemaInspector
{
    private const TTL_HOURS = 24;

    /** @var array<string, true> только положительные ответы */
    private array $tables = [];

    public function hasTable(string $table): bool
    {
        if ($this->tables[$table] ?? false) {
            return true;
        }

        $key = 'schema:has-table:'.config('database.default').':'.$table;

        try {
            if (Cache::get($key) === true) {
                return $this->tables[$table] = true;
            }
        } catch (Throwable) {
            // кеш недоступен: проверим таблицу напрямую
        }

        if (! Schema::hasTable($table)) {
            return false;
        }

        try {
            Cache::put($key, true, now()->addHours(self::TTL_HOURS));
        } catch (Throwable) {
            // не страшно: в следующий раз проверим снова
        }

        return $this->tables[$table] = true;
    }
}
