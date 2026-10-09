<?php

namespace App\Services;

use App\Application\Products\Queries\SearchProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CatalogPageService
{
    public function getCatalogPageData(Request $request): array
    {
        $priceBounds = Product::getCachedPriceRange();
        $filters = $this->resolveFilters($request, $priceBounds);
        $categories = $this->getCatalogMenuCategories();

        return [
            'categories' => $categories,
            'products' => $this->getCatalogProducts($request, null, $filters),
            'sellers' => Seller::getCachedAll(),
            'minPrice' => $priceBounds['min'],
            'maxPrice' => $priceBounds['max'],
            'categoryProductsCounts' => $this->getCategoryProductsCounts($categories),
        ];
    }

    public function getCategoryPageData(Category $category, Request $request): array
    {
        $priceBounds = Product::getCachedPriceRange();
        $filters = $this->resolveFilters($request, $priceBounds);
        $categoryIds = $this->getCategoryIds($category);
        $menuCategories = $this->getCategoryMenuCategories($category);

        return [
            'category' => $category,
            'categories' => $menuCategories,
            'breadcrumbs' => $category->breadcrumbs,
            'products' => $this->getCatalogProducts($request, $categoryIds, $filters),
            'sellers' => $this->getSellersForCategory($categoryIds),
            'minPrice' => $priceBounds['min'],
            'maxPrice' => $priceBounds['max'],
            'categoryProductsCount' => Product::getCategoryProductsCount($category->id),
            'categoryProductsCounts' => $this->getCategoryProductsCounts($menuCategories),
            'hasChildren' => $category->children->count() > 0,
        ];
    }

    public function getCatalogProducts(
        Request $request,
        ?array $categoryIds = null,
        ?array $filters = null,
        int $perPage = 12
    ): LengthAwarePaginator {
        $filters ??= $this->resolveFilters($request);

        return $this->baseCatalogQuery($categoryIds, $filters)
            ->paginate($perPage)
            ->appends($request->query());
    }

    public function getSaleProducts(int $perPage = 12): LengthAwarePaginator
    {
        return Product::visible()
            ->forRegionalContext()
            ->whereNotNull('lowprice')
            ->orderByDesc('sale_started_at')
            ->with(['images', 'seller'])
            ->paginate($perPage);
    }

    public function getSearchPageData(Request $request): array
    {
        $q = trim($request->input('q', ''));
        $categoryAlias = $request->input('category', '') ?? '';
        $sort = $request->input('sort', 'new');

        return [
            'products' => $this->getSearchProducts($q, $categoryAlias, $sort),
            'q' => $q,
            'selectedCategory' => $this->getSearchCategory($categoryAlias),
            'categoryAlias' => $categoryAlias,
        ];
    }

    public function getSearchProducts(
        string $q,
        ?string $categoryAlias = '',
        string $sort = 'new',
        int $perPage = 12
    ): LengthAwarePaginator
    {
        return app(SearchProducts::class)->execute($q, $categoryAlias, $sort, $perPage);
    }

    public function findActiveCategoryByAlias(string $alias): Category
    {
        return Category::where('alias', $alias)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function getCategoryIds(Category $category): array
    {
        return Category::descendantsAndSelf($category->id)->pluck('id')->toArray();
    }

    private function resolveFilters(Request $request, ?array $defaultPriceRange = null): array
    {
        $selectedSellerAlias = $request->input('seller');
        $sort = $request->input('sort', 'new');

        if (! in_array($sort, ['new', 'price_asc', 'sale'], true)) {
            $sort = 'new';
        }

        if ($request->has('seller')) {
            Session::put('catalog_filters.seller', $selectedSellerAlias);
        } elseif ($request->has('clear_seller')) {
            Session::forget('catalog_filters.seller');
            $selectedSellerAlias = null;
        }

        if ($request->has('min_price') && $request->has('max_price')) {
            $priceRange = [
                'min' => $request->input('min_price'),
                'max' => $request->input('max_price'),
            ];
            Session::put('catalog_filters.price', $priceRange);
        } else {
            $priceRange = Session::get('catalog_filters.price', $defaultPriceRange);
        }

        return [
            'seller' => $selectedSellerAlias,
            'price' => $priceRange,
            'sort' => $sort,
        ];
    }

    private function baseCatalogQuery(?array $categoryIds, array $filters): Builder
    {
        return Product::visible()
            ->forRegionalContext()
            ->when(! empty($categoryIds), function (Builder $query) use ($categoryIds) {
                $query->whereIn('category_id', $categoryIds);
            })
            ->when($filters['seller'] ?? null, function (Builder $query, string $sellerAlias) {
                $seller = Seller::getCachedByAlias($sellerAlias);

                if ($seller) {
                    $query->where('seller_id', $seller->id);
                }
            })
            ->when(! empty($filters['price']), function (Builder $query) use ($filters) {
                $query->whereBetween('price', [$filters['price']['min'], $filters['price']['max']]);
            })
            ->tap(fn (Builder $query) => $this->applySorting($query, $filters['sort'] ?? 'new'))
            ->with(['images', 'seller']);
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

    private function getCatalogMenuCategories(): Collection
    {
        $categories = Category::getCachedRootTree();

        if (! config('categories.hide_empty', true)) {
            return $categories;
        }

        return app(CatalogCacheService::class)
            ->rememberMenuCategories(fn () => Category::filterTreeByProducts($categories));
    }

    private function getCategoryMenuCategories(Category $category): Collection
    {
        $hasChildren = $category->children->count() > 0;
        $categories = $hasChildren
            ? $category->children
            : ($category->parent ? $category->parent->children : collect());

        if (! config('categories.hide_empty', true)) {
            return $categories;
        }

        // Поддерево одним запросом: иначе дети каждой категории подгружались бы по запросу на узел
        $parent = $hasChildren ? $category : $category->parent;

        if ($parent === null) {
            return collect();
        }

        return Category::filterTreeByProducts(
            Category::descendantsOf($parent->id)->toTree($parent->id)
        );
    }

    private function getCategoryProductsCounts(Collection $categories): array
    {
        return Product::getCategoryProductsCounts($categories->pluck('id')->all());
    }

    private function getSellersForCategory(array $categoryIds): Collection
    {
        return Seller::forRegionalContext()
            ->publiclyVisible()
            ->whereHas('products', function (Builder $query) use ($categoryIds) {
                $query->where('is_active', true)
                    ->whereIn('category_id', $categoryIds);
            })
            ->withCount(['products as product_count_in_category' => function (Builder $query) use ($categoryIds) {
                $query->where('is_active', true)
                    ->whereIn('category_id', $categoryIds);
            }])
            ->orderByDesc('product_count_in_category')
            ->get();
    }

    private function getSearchCategory(?string $categoryAlias): ?Category
    {
        $categoryAlias = $categoryAlias ?? '';

        if ($categoryAlias === '') {
            return null;
        }

        return Category::where('alias', $categoryAlias)->first();
    }
}
