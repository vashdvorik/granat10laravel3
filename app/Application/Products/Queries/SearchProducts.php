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
        int $perPage = 12
    ): LengthAwarePaginator {
        $normalizedQuery = $this->normalize($query);
        $tokens = $this->tokens($normalizedQuery);
        $categoryIds = $this->categoryIds($categoryAlias);

        if ($normalizedQuery !== '' && config('scout.driver') === 'meilisearch') {
            try {
                return $this->meilisearch($normalizedQuery, $categoryAlias, $categoryIds, $perPage);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $this->database($normalizedQuery, $tokens, $categoryAlias, $categoryIds, $perPage);
    }

    private function database(
        string $normalizedQuery,
        array $tokens,
        ?string $categoryAlias,
        array $categoryIds,
        int $perPage
    ): LengthAwarePaginator {
        return Product::visible()
            ->forRegionalContext()
            ->when($tokens !== [], fn (Builder $builder) => $this->applyTokens($builder, $tokens))
            ->when($categoryIds !== [], fn (Builder $builder) => $builder->whereIn('category_id', $categoryIds))
            ->when($normalizedQuery !== '', fn (Builder $builder) => $this->applyRelevance($builder, $normalizedQuery))
            ->orderByDesc('is_vip')
            ->orderByDesc('push_at')
            ->with(['images', 'seller'])
            ->paginate($perPage)
            ->appends([
                'q' => $normalizedQuery,
                'category' => $categoryAlias ?? '',
            ]);
    }

    private function meilisearch(
        string $query,
        ?string $categoryAlias,
        array $categoryIds,
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
