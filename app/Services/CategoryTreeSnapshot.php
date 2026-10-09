<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Строки для страницы управления категориями: дерево с раскрытием, поиск и «проблемные» категории.
 *
 * Читает всё дерево двумя лёгкими запросами (категории и число товаров по категориям) и считает остальное в памяти:
 * глубину, число подкатегорий и товаров с подкатегориями. На экран попадают только раскрытые ветки, поэтому страница
 * остаётся быстрой даже при сотнях и тысячах категорий.
 */
class CategoryTreeSnapshot
{
    private const SEARCH_LIMIT = 100;

    /**
     * @param  array<int, int|string>  $expanded  раскрытые категории
     * @return array{rows: array<int, array<string, mixed>>, total: int, problems: int, truncated: bool}
     */
    public function build(array $expanded = [], string $search = '', bool $onlyProblems = false): array
    {
        // Без моделей Eloquent: при тысячах категорий создание объектов занимало бы большую часть времени
        $categories = DB::table('categories')
            ->orderBy('_lft')
            ->get(['id', 'name', 'alias', 'parent_id', '_lft', '_rgt', 'is_active'])
            ->values();

        $direct = DB::table('products')
            ->select('category_id', DB::raw('count(*) as aggregate'))
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        $index = [];
        $childCount = [];
        $prefix = [0];

        foreach ($categories as $position => $category) {
            $index[$category->id] = $position;
            $childCount[$category->parent_id ?? 0] = ($childCount[$category->parent_id ?? 0] ?? 0) + 1;
            $prefix[] = $prefix[$position] + (int) ($direct[$category->id] ?? 0);
        }

        $depth = [];
        $siblings = [];
        $nodes = [];

        foreach ($categories as $position => $category) {
            $depth[$category->id] = $category->parent_id === null ? 0 : ($depth[$category->parent_id] ?? 0) + 1;
            $siblings[$category->parent_id ?? 0][] = $category->id;

            $descendants = intdiv(max(0, (int) $category->_rgt - (int) $category->_lft - 1), 2);
            $children = $childCount[$category->id] ?? 0;
            $own = (int) ($direct[$category->id] ?? 0);

            $nodes[$category->id] = [
                'id' => $category->id,
                'name' => (string) $category->name,
                'alias' => (string) $category->alias,
                'parent_id' => $category->parent_id,
                'depth' => $depth[$category->id],
                'has_children' => $children > 0,
                'children_count' => $children,
                'direct' => $own,
                'total' => $prefix[$position + $descendants + 1] - $prefix[$position],
                'active' => (bool) $category->is_active,
                // товары лежат в категории, у которой есть подкатегории: у них «осиротевшая» категория
                'problem' => $own > 0 && $children > 0,
                'path' => null,
                'first' => false,
                'last' => false,
                'expanded' => false,
            ];
        }

        foreach ($siblings as $ids) {
            $nodes[$ids[0]]['first'] = true;
            $nodes[$ids[array_key_last($ids)]]['last'] = true;
        }

        $problems = count(array_filter($nodes, fn (array $node): bool => $node['problem']));
        $search = trim($search);
        $truncated = false;

        if ($search !== '' || $onlyProblems) {
            $rows = [];

            foreach ($nodes as $node) {
                if ($onlyProblems && ! $node['problem']) {
                    continue;
                }

                if ($search !== '' && mb_stripos($node['name'], $search) === false && mb_stripos($node['alias'], $search) === false) {
                    continue;
                }

                if (count($rows) >= self::SEARCH_LIMIT) {
                    $truncated = true;

                    break;
                }

                $node['depth'] = 0;
                $node['path'] = $this->path($nodes, $node['parent_id']);
                $node['first'] = $node['last'] = false;   // порядок меняют в самом дереве, не в результатах поиска
                $rows[] = $node;
            }

            return ['rows' => $rows, 'total' => count($nodes), 'problems' => $problems, 'truncated' => $truncated];
        }

        $open = array_fill_keys(array_map('intval', $expanded), true);
        $visible = [];
        $rows = [];

        foreach ($nodes as $id => $node) {
            $parent = $node['parent_id'];
            $shown = $parent === null || (($open[$parent] ?? false) && ($visible[$parent] ?? false));
            $visible[$id] = $shown;

            if ($shown) {
                $node['expanded'] = $node['has_children'] && ($open[$id] ?? false);
                $rows[] = $node;
            }
        }

        return ['rows' => $rows, 'total' => count($nodes), 'problems' => $problems, 'truncated' => false];
    }

    /** Категория и все её родители: чтобы раскрыть дерево до нужного места. */
    public function ancestorsWithSelf(int $id): array
    {
        $parents = DB::table('categories')->pluck('parent_id', 'id');
        $ids = [];
        $guard = 0;

        while ($id !== null && $parents->has($id) && $guard++ < 30) {
            $ids[] = $id;
            $id = $parents[$id];
        }

        return $ids;
    }

    private function path(array $nodes, ?int $parentId): ?string
    {
        $names = [];
        $guard = 0;

        while ($parentId !== null && isset($nodes[$parentId]) && $guard++ < 20) {
            array_unshift($names, $nodes[$parentId]['name']);
            $parentId = $nodes[$parentId]['parent_id'];
        }

        return $names === [] ? null : implode(' → ', $names);
    }
}
