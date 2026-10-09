<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

/**
 * Варианты для поля «выберите категорию» в админке.
 *
 * В каталоге около 1600 категорий, поэтому список целиком в выпадающее поле не кладём: поле ищет по набранному
 * тексту (название или alias), а в подписи каждого варианта показан путь «Одежда → Женская → Платья».
 */
class CategoryPicker
{
    private const LIMIT = 50;

    /**
     * Категории, в которые можно поместить другую категорию. Саму категорию и всё её поддерево исключаем.
     * Без запроса показываем корневые категории: с них удобно начинать.
     *
     * @return array<int, string> [id => путь]
     */
    public static function parents(string $term, ?Category $exclude = null): array
    {
        $rows = self::rows();

        return self::format($rows, self::search($rows, $term, $exclude, leavesOnly: false, rootsWhenEmpty: true));
    }

    /**
     * Категории последнего уровня: только в них можно хранить товары.
     * Исключается только сама категория: переносить товары в её подкатегорию можно и нужно
     * (так исправляют «осиротевшие» товары родителя).
     *
     * @return array<int, string> [id => путь]
     */
    public static function leaves(string $term, ?Category $exclude = null): array
    {
        $rows = self::rows();

        return self::format($rows, self::search($rows, $term, $exclude, leavesOnly: true, rootsWhenEmpty: false, excludeSubtree: false));
    }

    /** Подпись выбранной категории: путь от корня. */
    public static function path(int|string|null $id): ?string
    {
        if (blank($id)) {
            return null;
        }

        $rows = self::rows();

        return isset($rows[(int) $id]) ? self::pathOf($rows, (int) $id) : null;
    }

    // ------------------------------------------------------------------ внутреннее

    /** @return array<int, array{id: int, name: string, alias: string, parent_id: ?int, lft: int, rgt: int}> в порядке дерева, ключ — id */
    private static function rows(): array
    {
        $rows = [];

        // Без моделей Eloquent: список читается при каждом поиске в выпадающем поле
        foreach (DB::table('categories')->orderBy('_lft')->get(['id', 'name', 'alias', 'parent_id', '_lft', '_rgt']) as $category) {
            $rows[(int) $category->id] = [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
                'alias' => (string) $category->alias,
                'parent_id' => $category->parent_id === null ? null : (int) $category->parent_id,
                'lft' => (int) $category->_lft,
                'rgt' => (int) $category->_rgt,
            ];
        }

        return $rows;
    }

    /** @return array<int, int> */
    private static function search(array $rows, string $term, ?Category $exclude, bool $leavesOnly, bool $rootsWhenEmpty, bool $excludeSubtree = true): array
    {
        $term = trim($term);
        $found = [];

        if ($term === '' && ! $rootsWhenEmpty) {
            return [];
        }

        foreach ($rows as $id => $row) {
            if ($exclude && ($excludeSubtree
                ? $row['lft'] >= (int) $exclude->_lft && $row['lft'] <= (int) $exclude->_rgt
                : $id === $exclude->getKey())) {
                continue;
            }

            if ($leavesOnly && $row['rgt'] !== $row['lft'] + 1) {
                continue;
            }

            $matches = $term === ''
                ? $row['parent_id'] === null
                : (mb_stripos($row['name'], $term) !== false || mb_stripos($row['alias'], $term) !== false);

            if ($matches) {
                $found[] = $id;
            }

            if (count($found) >= self::LIMIT) {
                break;
            }
        }

        return $found;
    }

    /** @param  array<int, int>  $ids */
    private static function format(array $rows, array $ids): array
    {
        $options = [];

        foreach ($ids as $id) {
            $options[$id] = self::pathOf($rows, $id);
        }

        return $options;
    }

    private static function pathOf(array $rows, int $id): string
    {
        $names = [];
        $guard = 0;

        while ($id !== null && isset($rows[$id]) && $guard++ < 20) {
            array_unshift($names, $rows[$id]['name']);
            $id = $rows[$id]['parent_id'];
        }

        return implode(' → ', $names);
    }
}
