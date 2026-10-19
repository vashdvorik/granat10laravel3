<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Settlement;
use App\Services\CatalogCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RegionalCatalogFilteringTest extends TestCase
{
    use RefreshDatabase;

    private Settlement $selectedSettlement;

    private Settlement $otherSettlement;

    private Seller $selectedSeller;

    private Seller $otherSeller;

    private Product $selectedProduct;

    private Product $otherProduct;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('regionality.enabled', true);
        config()->set('regionality.urls_enabled', true);
        Cache::flush();

        $this->selectedSettlement = Settlement::factory()->create();
        $this->otherSettlement = Settlement::factory()->create();
        $this->selectedSeller = $this->createSeller($this->selectedSettlement, 'selected-shop');
        $this->otherSeller = $this->createSeller($this->otherSettlement, 'other-shop');
        $category = Category::factory()->create(['is_active' => true]);
        $this->selectedProduct = $this->createProduct($this->selectedSeller, $category, 'Selected Regional Product');
        $this->otherProduct = $this->createProduct($this->otherSeller, $category, 'Other Regional Product');
    }

    public function test_catalog_and_ajax_only_show_products_from_selected_settlement(): void
    {
        foreach ([$this->regional('catalog.index'), $this->regional('catalog.loadMore')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee($this->selectedProduct->name)
                ->assertDontSee($this->otherProduct->name);
        }
    }

    public function test_search_and_sale_only_show_products_from_selected_settlement(): void
    {
        $this->selectedProduct->update(['lowprice' => 50, 'sale_started_at' => now()]);
        $this->otherProduct->update(['lowprice' => 50, 'sale_started_at' => now()]);

        $this->get($this->regional('search', ['q' => 'Regional Product']))
            ->assertOk()
            ->assertSee($this->selectedProduct->name)
            ->assertDontSee($this->otherProduct->name);

        $this->get($this->regional('catalog.sale'))
            ->assertOk()
            ->assertSee($this->selectedProduct->name)
            ->assertDontSee($this->otherProduct->name);
    }

    public function test_shops_and_shop_details_are_limited_to_selected_settlement(): void
    {
        $this->get($this->regional('shops'))
            ->assertOk()
            ->assertSee($this->selectedSeller->name)
            ->assertDontSee($this->otherSeller->name);

        $this->get($this->regional('seller', ['alias' => $this->otherSeller->alias]))
            ->assertNotFound();
    }

    public function test_product_details_and_favorites_reject_products_from_other_settlement(): void
    {
        $this->get($this->regional('product.show', ['alias' => $this->otherProduct->alias]))
            ->assertNotFound();

        $this->getJson($this->regional('favorites.products', [
                'ids' => $this->selectedProduct->id.','.$this->otherProduct->id,
            ]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertSee($this->selectedProduct->name)
            ->assertDontSee($this->otherProduct->name);
    }

    public function test_all_regions_mode_keeps_all_products_and_sellers_visible(): void
    {
        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee($this->selectedProduct->name)
            ->assertSee($this->otherProduct->name);

        $this->get(route('shops'))
            ->assertOk()
            ->assertSee($this->selectedSeller->name)
            ->assertSee($this->otherSeller->name);
    }

    /** Сессия и cookie со старым выбором города не фильтруют общие адреса: фильтр включается только адресом. */
    public function test_stored_selection_does_not_filter_common_addresses(): void
    {
        $cookieName = config('regionality.cookie_name');

        foreach ([route('home'), route('catalog.index'), route('catalog.loadMore'), route('shops')] as $url) {
            $this->withSession(['regional_settlement_id' => $this->selectedSettlement->id])
                ->withCookie($cookieName, (string) $this->selectedSettlement->id)
                ->get($url)
                ->assertOk();
        }

        $this->withSession(['regional_settlement_id' => $this->selectedSettlement->id])
            ->withCookie($cookieName, (string) $this->selectedSettlement->id)
            ->get(route('catalog.index'))
            ->assertSee($this->selectedProduct->name)
            ->assertSee($this->otherProduct->name);
    }

    public function test_regional_cache_keys_are_isolated(): void
    {
        $cache = app(CatalogCacheService::class);

        $this->get($this->regional('catalog.index'))->assertOk();

        $this->assertTrue(Cache::has(
            CatalogCacheService::PRODUCT_PRICE_RANGE_KEY.':settlement:'.$this->selectedSettlement->id
        ));
        $this->assertTrue(Cache::has(
            CatalogCacheService::SELLERS_LIST_KEY.':settlement:'.$this->selectedSettlement->id
        ));
        $this->assertFalse(Cache::has(CatalogCacheService::PRODUCT_PRICE_RANGE_KEY));
        $this->assertSame(
            CatalogCacheService::SELLERS_LIST_KEY.':settlement:'.$this->selectedSettlement->id,
            $cache->regionalKey(CatalogCacheService::SELLERS_LIST_KEY)
        );
    }

    public function test_regional_menu_hides_category_with_products_only_in_other_settlement(): void
    {
        $selectedCategory = Category::factory()->create([
            'name' => 'Selected City Category',
            'is_active' => true,
        ]);
        $otherCategory = Category::factory()->create([
            'name' => 'Other City Category',
            'is_active' => true,
        ]);

        $this->createProduct($this->selectedSeller, $selectedCategory, 'Selected Category Product');
        $this->createProduct($this->otherSeller, $otherCategory, 'Other Category Product');

        $this->get($this->regional('catalog.index'))
            ->assertOk()
            ->assertSee($selectedCategory->name)
            ->assertDontSee($otherCategory->name);
    }

    /** Адрес региональной страницы выбранного города: город определяется только адресом. */
    private function regional(string $name, array $parameters = []): string
    {
        return route('regional.'.$name, ['location' => $this->selectedSettlement->slug, ...$parameters]);
    }

    private function createSeller(Settlement $settlement, string $alias): Seller
    {
        $seller = Seller::factory()->for($settlement)->create(['alias' => $alias]);


        return $seller;
    }

    private function createProduct(Seller $seller, Category $category, string $name): Product
    {
        return Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => $name,
            'is_active' => true,
            'price' => 100,
        ]);
    }
}
