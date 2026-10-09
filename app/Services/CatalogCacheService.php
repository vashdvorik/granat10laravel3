<?php

namespace App\Services;

use App\Jobs\WarmCategoriesCache;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Settlement;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CatalogCacheService
{
    public const ROOT_TREE_KEY = 'categories_root_tree';

    public const ROOT_LIST_KEY = 'categories_root_list';

    public const MENU_CATEGORIES_KEY = 'menu_categories';

    public const PRODUCT_PRICE_RANGE_KEY = 'product_price_range';

    public const SELLERS_LIST_KEY = 'sellers_list';

    private const SELLER_BY_ALIAS_PREFIX = 'seller_by_alias:';

    private const CATEGORY_PRODUCTS_COUNT_PREFIX = 'category_products_count:';

    public function rememberRootTree(Closure $callback): mixed
    {
        return Cache::remember(self::ROOT_TREE_KEY, now()->addHours(24), $callback);
    }

    public function rememberRootCategories(Closure $callback): mixed
    {
        return Cache::remember(self::ROOT_LIST_KEY, now()->addHours(24), $callback);
    }

    public function rememberMenuCategories(Closure $callback): mixed
    {
        return Cache::remember($this->regionalKey(self::MENU_CATEGORIES_KEY), now()->addHours(24), $callback);
    }

    public function rememberProductPriceRange(Closure $callback): mixed
    {
        return Cache::remember($this->regionalKey(self::PRODUCT_PRICE_RANGE_KEY), now()->addHour(), $callback);
    }

    public function rememberSellers(Closure $callback): mixed
    {
        return Cache::remember($this->regionalKey(self::SELLERS_LIST_KEY), now()->addMinutes(30), $callback);
    }

    public function rememberSellerByAlias(string $alias, Closure $callback): mixed
    {
        return Cache::remember($this->regionalKey($this->sellerByAliasKey($alias)), now()->addHour(), $callback);
    }

    public function rememberCategoryProductsCount(int $categoryId, Closure $callback): mixed
    {
        return Cache::remember(
            $this->regionalKey($this->categoryProductsCountKey($categoryId)),
            now()->addMinutes(10),
            $callback
        );
    }

    public function forgetCategories(): void
    {
        Cache::forget(self::ROOT_TREE_KEY);
        Cache::forget(self::ROOT_LIST_KEY);
        $this->forgetMenuCategories();
    }

    public function forgetMenuCategories(): void
    {
        $this->forgetRegionalVariants(self::MENU_CATEGORIES_KEY);
    }

    public function forgetProductPriceRange(): void
    {
        $this->forgetRegionalVariants(self::PRODUCT_PRICE_RANGE_KEY);
    }

    public function forgetSellers(): void
    {
        $this->forgetRegionalVariants(self::SELLERS_LIST_KEY);
    }

    public function forgetSellerByAlias(string $alias): void
    {
        $this->forgetRegionalVariants($this->sellerByAliasKey($alias));
    }

    public function forgetCategoryProductsCount(int $categoryId): void
    {
        $this->forgetRegionalVariants($this->categoryProductsCountKey($categoryId));
    }

    public function forgetProductCatalog(?Category $category = null, ?int $oldCategoryId = null): void
    {
        $this->forgetProductPriceRange();
        $this->forgetMenuCategories();

        if ($category) {
            $this->forgetCategoryAndAncestors($category);
        }

        if ($oldCategoryId && $oldCategoryId !== $category?->id) {
            $oldCategory = Category::find($oldCategoryId);

            if ($oldCategory) {
                $this->forgetCategoryAndAncestors($oldCategory);
            }
        }
    }

    public function forgetProductRelated(
        Product $product,
        ?int $oldCategoryId = null,
        ?int $oldSellerId = null
    ): void {
        $category = Category::find($product->category_id);
        $seller = Seller::find($product->seller_id);

        $this->forgetProductCatalog($category, $oldCategoryId);
        $this->forgetSellerRelated($seller);

        if ($oldSellerId && $oldSellerId !== $product->seller_id) {
            $oldSeller = Seller::find($oldSellerId);

            if ($oldSeller) {
                $this->forgetSellerRelated($oldSeller);
            }
        }
    }

    public function forgetSellerRelated(?Seller $seller = null, bool $visibilityChanged = false): void
    {
        $this->forgetSellers();

        if ($seller) {
            $this->forgetSellerByAlias($seller->alias);

            $oldAlias = $seller->getOriginal('alias');

            if ($oldAlias && $oldAlias !== $seller->alias) {
                $this->forgetSellerByAlias($oldAlias);
            }
        }

        if (!$visibilityChanged) {
            return;
        }

        $this->forgetProductPriceRange();
        $this->forgetMenuCategories();

        if ($seller) {
            $this->forgetSellerCategoryCounts($seller);
        }
    }

    public function forgetCategoryRelated(bool $warm = true): void
    {
        $this->forgetCategories();

        if (!$warm) {
            return;
        }

        if (app()->runningInConsole()) {
            dispatch(new WarmCategoriesCache());
        } else {
            dispatch(new WarmCategoriesCache())->afterResponse();
        }
    }

    public function sellerByAliasKey(string $alias): string
    {
        return self::SELLER_BY_ALIAS_PREFIX.$alias;
    }

    public function categoryProductsCountKey(int $categoryId): string
    {
        return self::CATEGORY_PRODUCTS_COUNT_PREFIX.$categoryId;
    }

    public function regionalKey(string $baseKey): string
    {
        $settlementId = app(RegionalContext::class)->settlement()?->id;

        return $settlementId ? $baseKey.':settlement:'.$settlementId : $baseKey;
    }

    private function forgetRegionalVariants(string $baseKey): void
    {
        Cache::forget($baseKey);

        try {
            if (! app(\App\Services\SchemaInspector::class)->hasTable('settlements')) {
                return;
            }

            Settlement::query()->pluck('id')->each(
                fn (int $settlementId) => Cache::forget($baseKey.':settlement:'.$settlementId)
            );
        } catch (Throwable) {
            return;
        }
    }

    /** Сбрасывает счётчики товаров категории и всех её родителей (после переноса категорий и товаров). */
    public function forgetCategoryProductsCountWithAncestors(Category $category): void
    {
        $this->forgetCategoryAndAncestors($category);
    }

    private function forgetCategoryAndAncestors(Category $category): void
    {
        $this->forgetCategoryProductsCount($category->id);

        foreach ($category->ancestors as $ancestor) {
            $this->forgetCategoryProductsCount($ancestor->id);
        }
    }

    private function forgetSellerCategoryCounts(Seller $seller): void
    {
        $categories = Category::query()
            ->whereIn('id', $seller->products()->select('category_id'))
            ->get();

        foreach ($categories as $category) {
            $this->forgetCategoryAndAncestors($category);
        }
    }
}
