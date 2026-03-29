<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature-тест: кеширование продавцов.
 *
 * @covers \App\Models\Seller::getCachedAll
 * @covers \App\Models\Seller::getCachedByAlias
 * @covers \App\Models\Seller::clearSellersCache
 */
class SellerCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // =========================================================================
    // getCachedByAlias
    // =========================================================================

    public function test_get_cached_by_alias_returns_seller(): void
    {
        $seller = Seller::factory()->create(['alias' => 'test-shop-cache-1']);

        $found = Seller::getCachedByAlias('test-shop-cache-1');

        $this->assertNotNull($found);
        $this->assertSame($seller->id, $found->id);
    }

    public function test_get_cached_by_alias_returns_null_for_nonexistent(): void
    {
        $seller = Seller::getCachedByAlias('shop-that-does-not-exist-xyz999');

        $this->assertNull($seller);
    }

    public function test_get_cached_by_alias_returns_null_for_empty_string(): void
    {
        $this->assertNull(Seller::getCachedByAlias(''));
        $this->assertNull(Seller::getCachedByAlias(null));
    }

    public function test_get_cached_by_alias_caches_result(): void
    {
        $seller = Seller::factory()->create(['alias' => 'test-shop-cache-2']);

        // Первый вызов записывает в кеш
        Seller::getCachedByAlias('test-shop-cache-2');

        $cacheKey = 'seller_by_alias:test-shop-cache-2';
        $this->assertTrue(Cache::has($cacheKey), 'Продавец по alias должен кешироваться');
    }

    // =========================================================================
    // getCachedAll
    // =========================================================================

    public function test_get_cached_all_returns_sellers_collection(): void
    {
        Seller::factory()->count(2)->create();

        $sellers = Seller::getCachedAll();

        $this->assertNotEmpty($sellers);
        $this->assertGreaterThanOrEqual(2, $sellers->count());
    }

    public function test_get_cached_all_caches_result(): void
    {
        Seller::factory()->create();
        Cache::forget('sellers_list');

        Seller::getCachedAll();

        $this->assertTrue(Cache::has('sellers_list'), 'Список продавцов должен кешироваться');
    }

    // =========================================================================
    // clearSellersCache
    // =========================================================================

    public function test_clear_sellers_cache_removes_list_cache(): void
    {
        Cache::put('sellers_list', collect([]), 3600);

        Seller::clearSellersCache();

        $this->assertFalse(Cache::has('sellers_list'));
    }

    public function test_clear_sellers_cache_removes_seller_by_alias_cache(): void
    {
        $seller = Seller::factory()->create(['alias' => 'test-cache-invalidation']);
        Cache::put('seller_by_alias:test-cache-invalidation', $seller, 3600);

        Seller::clearSellersCache($seller);

        $this->assertFalse(Cache::has('seller_by_alias:test-cache-invalidation'));
    }

    // =========================================================================
    // Автоматическая инвалидация при изменении модели
    // =========================================================================

    public function test_cache_invalidated_on_seller_update(): void
    {
        $seller = Seller::factory()->create(['alias' => 'test-cache-update-seller']);
        Cache::put('sellers_list', collect([$seller]), 3600);
        Cache::put('seller_by_alias:' . $seller->alias, $seller, 3600);

        // Обновляем продавца — должен сработать booted()
        $seller->update(['name' => 'Обновлённое название']);

        $this->assertFalse(Cache::has('sellers_list'), 'Список должен быть сброшен после обновления');
    }

    public function test_cache_invalidated_on_seller_created(): void
    {
        Cache::put('sellers_list', collect([]), 3600);

        Seller::factory()->create();

        $this->assertFalse(Cache::has('sellers_list'), 'Список должен быть сброшен после создания продавца');
    }
}
