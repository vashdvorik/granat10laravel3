<?php

namespace App\Application\Products\Queries;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Throwable;

class SearchProducts
{
    private const SEARCHABLE_COLUMNS = [
        'name',
        'desc',
        'features1',
        'features2',
        'features3',
        'features4',
    ];

    public function execute(
        string $query,
        ?string $categoryAlias = null,
        string $sort = 'new',
        int $perPage = 12
    ): LengthAwarePaginator {
        $normalizedQuery = $this->normalize($query);
        $tokens = $this->tokens($normalizedQuery);
        $categoryIds = $this->categoryIds($categoryAlias);
        $sort = $this->normalizeSort($sort);

        if ($sort === 'new' && $normalizedQuery !== '' && config('scout.driver') === 'meilisearch') {
            try {
                return $this->meilisearch($normalizedQuery, $categoryAlias, $categoryIds, $sort, $perPage);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $this->database($normalizedQuery, $tokens, $categoryAlias, $categoryIds, $sort, $perPage);
    }

    private function database(
        string $normalizedQuery,
        array $tokens,
        ?string $categoryAlias,
        array $categoryIds,
        string $sort,
        int $perPage
    ): LengthAwarePaginator {
        return Product::visible()
            ->forRegionalContext()
            ->when($tokens !== [], fn (Builder $builder) => $this->applyTokens($builder, $tokens))
            ->when($categoryIds !== [], fn (Builder $builder) => $builder->whereIn('category_id', $categoryIds))
            ->when(
                $normalizedQuery !== '' && $sort === 'new',
                fn (Builder $builder) => $this->applyRelevance($builder, $normalizedQuery)
            )
            ->tap(fn (Builder $builder) => $this->applySorting($builder, $sort))
            ->with(['images', 'seller'])
            ->paginate($perPage)
            ->appends([
                'q' => $normalizedQuery,
                'category' => $categoryAlias ?? '',
                'sort' => $sort,
            ]);
    }

    private function meilisearch(
        string $query,
        ?string $categoryAlias,
        array $categoryIds,
        string $sort,
        int $perPage
    ): LengthAwarePaginator {
        $search = Product::search($query)
            ->query(fn (Builder $builder) => $builder
                ->visible()
                ->forRegionalContext()
                ->with(['images', 'seller']))
            ->orderBy('is_vip', 'desc')
            ->orderBy('push_at', 'desc');

        if ($categoryIds !== []) {
            $search->whereIn('category_id', $categoryIds);
        }

        $settlement = app(\App\Services\RegionalContext::class)->settlement();

        if ($settlement !== null) {
            $search->where('settlement_id', $settlement->id);
        }

        return $search->paginate($perPage)
            ->appends([
                'q' => $query,
                'category' => $categoryAlias ?? '',
                'sort' => $sort,
            ]);
    }

    public function normalize(string $query): string
    {
        return Str::of($query)
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->limit(100, '')
            ->toString();
    }

    private function tokens(string $query): array
    {
        if ($query === '') {
            return [];
        }

        $tokens = collect(preg_split('/\s+/u', $query) ?: [])
            ->filter(fn (string $token): bool => mb_strlen($token) >= 2)
            ->unique()
            ->take(8)
            ->values()
            ->all();

        return $tokens === [] ? [$query] : $tokens;
    }

    private function applyTokens(Builder $query, array $tokens): void
    {
        foreach ($tokens as $token) {
            $pattern = '%'.$this->escapeLike($token).'%';

            $query->where(function (Builder $tokenQuery) use ($pattern): void {
                foreach (self::SEARCHABLE_COLUMNS as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $tokenQuery->{$method}($column, 'LIKE', $pattern);
                }
            });
        }
    }

    private function applyRelevance(Builder $query, string $search): void
    {
        $escaped = $this->escapeLike($search);

        $query->orderByRaw(
            'CASE
                WHEN name = ? THEN 0
                WHEN name LIKE ? THEN 1
                WHEN name LIKE ? THEN 2
                WHEN features1 LIKE ? OR features2 LIKE ?
                    OR features3 LIKE ? OR features4 LIKE ? THEN 3
                WHEN `desc` LIKE ? THEN 4
                ELSE 5
            END',
            [
                $search,
                $escaped.'%',
                '%'.$escaped.'%',
                '%'.$escaped.'%',
                '%'.$escaped.'%',
                '%'.$escaped.'%',
                '%'.$escaped.'%',
                '%'.$escaped.'%',
            ]
        );
    }

    private function applySorting(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query
                ->orderByRaw(
                    'CASE WHEN lowprice IS NOT NULL AND lowprice > 0 AND lowprice < price THEN lowprice ELSE price END ASC'
                )
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
            'sale' => $query
                ->orderByRaw('CASE WHEN lowprice IS NOT NULL AND lowprice > 0 AND lowprice < price THEN 1 ELSE 0 END DESC')
                ->orderByRaw('CASE WHEN lowprice IS NOT NULL AND lowprice > 0 AND lowprice < price THEN (price - lowprice) / NULLIF(price, 0) ELSE 0 END DESC')
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
            default => $query
                ->orderByVipFirst()
                ->orderByDesc('push_at')
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
        };
    }

    private function normalizeSort(string $sort): string
    {
        return in_array($sort, ['new', 'price_asc', 'sale'], true) ? $sort : 'new';
    }

    private function categoryIds(?string $categoryAlias): array
    {
        if ($categoryAlias === null || $categoryAlias === '') {
            return [];
        }

        $category = Category::query()
            ->where('alias', $categoryAlias)
            ->where('is_active', true)
            ->first();

        return $category
            ? Category::descendantsAndSelf($category->id)->pluck('id')->all()
            : [];
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
