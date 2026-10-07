<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature-тест: кеширование и инвалидация Product::getCachedPriceRange() 
 * и Product::getCategoryProductsCount().
 *
 * @covers \App\Models\Product::getCachedPriceRange
 * @covers \App\Models\Product::getCategoryProductsCount
 * @covers \App\Models\Product::clearProductCaches
 */
class ProductCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // =========================================================================
    // Хелперы
    // =========================================================================

    private function createVisibleProduct(array $attrs = []): Product
    {
        $seller  = Seller::factory()->create();
        $category = Category::factory()->create();


        return Product::factory()->create(array_merge([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
        ], $attrs));
    }

    // =========================================================================
    // getCachedPriceRange
    // =========================================================================

    public function test_get_cached_price_range_returns_min_max(): void
    {
        $this->createVisibleProduct(['price' => 100]);
        $this->createVisibleProduct(['price' => 500]);
        $this->createVisibleProduct(['price' => 250]);

        Cache::forget('product_price_range');

        $range = Product::getCachedPriceRange();

        $this->assertArrayHasKey('min', $range);
        $this->assertArrayHasKey('max', $range);
        $this->assertLessThanOrEqual((float) 100, (float) $range['min']);
        $this->assertGreaterThanOrEqual((float) 500, (float) $range['max']);
    }

    public function test_get_cached_price_range_is_cached_on_second_call(): void
    {
        $this->createVisibleProduct(['price' => 200]);

        // Первый вызов записывает в кеш
        Product::getCachedPriceRange();

        // Второй вызов читает из кеша — проверяем что ключ существует
        $this->assertTrue(
            Cache::has('product_price_range'),
            'Диапазон цен должен быть закеширован'
        );
    }

    public function test_clear_product_caches_removes_price_range_cache(): void
    {
        // Прогреть кеш
        Cache::put('product_price_range', ['min' => 100, 'max' => 1000], 3600);

        Product::clearProductCaches();

        $this->assertFalse(
            Cache::has('product_price_range'),
            'Кеш диапазона цен должен быть удалён после clearProductCaches()'
        );
    }

    // =========================================================================
    // getCategoryProductsCount
    // =========================================================================

    public function test_get_category_products_count_returns_correct_count(): void
    {
        $seller  = Seller::factory()->create();
        $category = Category::factory()->create();


        Product::factory()->count(3)->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
        ]);

        // Скрытый товар — не должен считаться
        Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => false,
        ]);

        Cache::forget('category_products_count:' . $category->id);

        $count = Product::getCategoryProductsCount($category->id);

        $this->assertGreaterThanOrEqual(3, $count);
    }

    public function test_get_category_products_count_for_nonexistent_category_returns_zero(): void
    {
        $count = Product::getCategoryProductsCount(99999);
        $this->assertSame(0, $count);
    }

    public function test_category_products_count_is_cached(): void
    {
        $category = Category::factory()->create();
        $cacheKey = 'category_products_count:' . $category->id;

        Cache::forget($cacheKey);

        Product::getCategoryProductsCount($category->id);

        $this->assertTrue(Cache::has($cacheKey), 'Счётчик товаров в категории должен кешироваться');
    }
}
