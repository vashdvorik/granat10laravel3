<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Unit-тест: корректность инвалидации кешей.
 * Используем Cache::spy() чтобы убедиться, что нужные ключи сбрасываются.
 *
 * @covers \App\Models\Product::clearProductCaches
 * @covers \App\Models\Seller::clearSellersCache
 */
class CacheInvalidationUnitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::spy();
    }

    // =========================================================================
    // Product caches
    // =========================================================================

    public function test_clear_product_caches_always_forgets_price_range(): void
    {
        Product::clearProductCaches();

        Cache::shouldHaveReceived('forget')->with('product_price_range')->once();
    }

    public function test_clear_product_caches_forgets_category_count_cache(): void
    {
        /** @var Category $category */
        $category = \Mockery::mock(Category::class)->makePartial();
        $category->id = 42;

        // ancestors пустой — нет родителей
        $category->shouldReceive('getAttribute')
            ->with('ancestors')
            ->andReturn(collect());

        Product::clearProductCaches($category);

        Cache::shouldHaveReceived('forget')->with('category_products_count:42')->once();
    }

    public function test_clear_product_caches_always_forgets_price_range_with_old_category(): void
    {
        /** @var Category $newCategory */
        $newCategory = \Mockery::mock(Category::class)->makePartial();
        $newCategory->id = 10;
        $newCategory->shouldReceive('getAttribute')->with('ancestors')->andReturn(collect());

        // Симулируем смену категории товара: oldCategoryId == текущий → ветка не сработает
        Product::clearProductCaches($newCategory, oldCategoryId: 10);

        // product_price_range сбрасывается в любом случае
        Cache::shouldHaveReceived('forget')->with('product_price_range')->once();
    }

    // =========================================================================
    // Seller caches
    // =========================================================================

    public function test_clear_sellers_cache_forgets_list(): void
    {
        Seller::clearSellersCache();

        Cache::shouldHaveReceived('forget')->with('sellers_list')->once();
    }

    public function test_clear_sellers_cache_forgets_seller_by_alias(): void
    {
        /** @var Seller $seller */
        $seller = new Seller();
        $seller->alias = 'my-shop';

        Seller::clearSellersCache($seller);

        Cache::shouldHaveReceived('forget')->with('sellers_list')->once();
        Cache::shouldHaveReceived('forget')->with('seller_by_alias:my-shop')->once();
    }

    public function test_product_scope_visible_name(): void
    {
        // Проверяем, что scope существует в модели (статический анализ)
        $this->assertTrue(
            method_exists(Product::class, 'scopeVisible'),
            'Product должен иметь scope "visible"'
        );
    }

    public function test_product_scope_from_active_seller_package_exists(): void
    {
        $this->assertTrue(
            method_exists(Product::class, 'scopeFromActiveSellerPackage'),
            'Product должен иметь scope "fromActiveSellerPackage"'
        );
    }
}
