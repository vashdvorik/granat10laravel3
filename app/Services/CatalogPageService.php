<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
            ->whereNotNull('lowprice')
            ->orderByDesc('sale_started_at')
            ->with(['images', 'seller'])
            ->paginate($perPage);
    }

    public function getSearchPageData(Request $request): array
    {
        $q = trim($request->input('q', ''));
        $categoryAlias = $request->input('category', '');

        return [
            'products' => $this->getSearchProducts($q, $categoryAlias),
            'q' => $q,
            'selectedCategory' => $this->getSearchCategory($categoryAlias),
            'categoryAlias' => $categoryAlias,
        ];
    }

    public function getSearchProducts(string $q, string $categoryAlias = '', int $perPage = 12): LengthAwarePaginator
    {
        $category = $this->getSearchCategory($categoryAlias);
        $categoryIds = $category ? $this->getCategoryIds($category) : [];

        return Product::visible()
            ->when($q !== '', function (Builder $query) use ($q) {
                $query->where(function (Builder $subQuery) use ($q) {
                    $subQuery->where('name', 'LIKE', '%' . $q . '%')
                        ->orWhere('desc', 'LIKE', '%' . $q . '%')
                        ->orWhere('features1', 'LIKE', '%' . $q . '%')
                        ->orWhere('features2', 'LIKE', '%' . $q . '%')
                        ->orWhere('features3', 'LIKE', '%' . $q . '%')
                        ->orWhere('features4', 'LIKE', '%' . $q . '%');
                });
            })
            ->when(!empty($categoryIds), function (Builder $query) use ($categoryIds) {
                $query->whereIn('category_id', $categoryIds);
            })
            ->orderByDesc('is_vip')
            ->orderByDesc('push_at')
            ->with(['images', 'seller'])
            ->paginate($perPage)
            ->appends(['q' => $q, 'category' => $categoryAlias]);
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
        ];
    }

    private function baseCatalogQuery(?array $categoryIds, array $filters): Builder
    {
        return Product::visible()
            ->when(!empty($categoryIds), function (Builder $query) use ($categoryIds) {
                $query->whereIn('category_id', $categoryIds);
            })
            ->when($filters['seller'] ?? null, function (Builder $query, string $sellerAlias) {
                $seller = Seller::getCachedByAlias($sellerAlias);

                if ($seller) {
                    $query->where('seller_id', $seller->id);
                }
            })
            ->when(!empty($filters['price']), function (Builder $query) use ($filters) {
                $query->whereBetween('price', [$filters['price']['min'], $filters['price']['max']]);
            })
            ->orderByDesc('is_vip')
            ->orderByDesc('push_at')
            ->with(['images', 'seller']);
    }

    private function getCatalogMenuCategories(): Collection
    {
        $categories = Category::getCachedRootTree();

        if (!config('categories.hide_empty', true)) {
            return $categories;
        }

        return Cache::remember('menu_categories', now()->addHours(24), fn () => Category::filterTreeByProducts($categories));
    }

    private function getCategoryMenuCategories(Category $category): Collection
    {
        $hasChildren = $category->children->count() > 0;
        $categories = $hasChildren
            ? $category->children
            : ($category->parent ? $category->parent->children : collect());

        if (!config('categories.hide_empty', true)) {
            return $categories;
        }

        return Category::filterTreeByProducts($categories);
    }

    private function getCategoryProductsCounts(Collection $categories): array
    {
        $counts = [];

        foreach ($categories as $category) {
            $counts[$category->id] = Product::getCategoryProductsCount($category->id);
        }

        return $counts;
    }

    private function getSellersForCategory(array $categoryIds): Collection
    {
        return Seller::whereHas('sellerPackages', function (Builder $query) {
                $query->where('is_active', true)
                    ->where(function (Builder $dateQuery) {
                        $dateQuery->whereNull('end_date')
                            ->orWhere('end_date', '>=', now()->toDateString());
                    });
            })
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

    private function getSearchCategory(string $categoryAlias): ?Category
    {
        if ($categoryAlias === '') {
            return null;
        }

        return Category::where('alias', $categoryAlias)->first();
    }
}
