<?php

namespace Tests\Unit;

use App\Services\CatalogCacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogCacheServiceTest extends TestCase
{
    private CatalogCacheService $cache;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->cache = app(CatalogCacheService::class);
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    public function test_remember_methods_reuse_cached_values(): void
    {
        $calls = 0;
        $callback = function () use (&$calls): array {
            $calls++;

            return ['value' => $calls];
        };

        $first = $this->cache->rememberMenuCategories($callback);
        $second = $this->cache->rememberMenuCategories($callback);

        $this->assertSame(['value' => 1], $first);
        $this->assertSame($first, $second);
        $this->assertSame(1, $calls);
    }

    public function test_seller_alias_keys_are_isolated(): void
    {
        $first = $this->cache->rememberSellerByAlias('first-shop', fn () => 'first');
        $second = $this->cache->rememberSellerByAlias('second-shop', fn () => 'second');

        $this->assertSame('first', $first);
        $this->assertSame('second', $second);
        $this->assertSame('seller_by_alias:first-shop', $this->cache->sellerByAliasKey('first-shop'));
        $this->assertSame('seller_by_alias:second-shop', $this->cache->sellerByAliasKey('second-shop'));
    }

    public function test_forget_categories_clears_all_category_menu_keys(): void
    {
        Cache::put(CatalogCacheService::ROOT_TREE_KEY, 'tree');
        Cache::put(CatalogCacheService::ROOT_LIST_KEY, 'list');
        Cache::put(CatalogCacheService::MENU_CATEGORIES_KEY, 'menu');

        $this->cache->forgetCategories();

        $this->assertFalse(Cache::has(CatalogCacheService::ROOT_TREE_KEY));
        $this->assertFalse(Cache::has(CatalogCacheService::ROOT_LIST_KEY));
        $this->assertFalse(Cache::has(CatalogCacheService::MENU_CATEGORIES_KEY));
    }

    public function test_targeted_forget_methods_clear_only_requested_keys(): void
    {
        $categoryKey = $this->cache->categoryProductsCountKey(42);
        $sellerKey = $this->cache->sellerByAliasKey('shop');

        Cache::put(CatalogCacheService::PRODUCT_PRICE_RANGE_KEY, ['min' => 10, 'max' => 20]);
        Cache::put(CatalogCacheService::SELLERS_LIST_KEY, ['shop']);
        Cache::put($categoryKey, 5);
        Cache::put($sellerKey, 'seller');

        $this->cache->forgetProductPriceRange();
        $this->cache->forgetSellers();
        $this->cache->forgetCategoryProductsCount(42);
        $this->cache->forgetSellerByAlias('shop');

        $this->assertFalse(Cache::has(CatalogCacheService::PRODUCT_PRICE_RANGE_KEY));
        $this->assertFalse(Cache::has(CatalogCacheService::SELLERS_LIST_KEY));
        $this->assertFalse(Cache::has($categoryKey));
        $this->assertFalse(Cache::has($sellerKey));
    }
}
