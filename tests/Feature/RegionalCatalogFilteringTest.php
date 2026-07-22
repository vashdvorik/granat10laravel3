<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
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
        Cache::flush();

        $this->selectedSettlement = Settlement::factory()->create();
        $this->otherSettlement = Settlement::factory()->create();
        $this->selectedSeller = $this->createSellerWithPackage($this->selectedSettlement, 'selected-shop');
        $this->otherSeller = $this->createSellerWithPackage($this->otherSettlement, 'other-shop');
        $category = Category::factory()->create(['is_active' => true]);
        $this->selectedProduct = $this->createProduct($this->selectedSeller, $category, 'Selected Regional Product');
        $this->otherProduct = $this->createProduct($this->otherSeller, $category, 'Other Regional Product');
    }

    public function test_catalog_and_ajax_only_show_products_from_selected_settlement(): void
    {
        foreach ([route('catalog.index'), route('catalog.loadMore')] as $url) {
            $this->withSelectedSettlement()
                ->get($url)
                ->assertOk()
                ->assertSee($this->selectedProduct->name)
                ->assertDontSee($this->otherProduct->name);
        }
    }

    public function test_search_and_sale_only_show_products_from_selected_settlement(): void
    {
        $this->selectedProduct->update(['lowprice' => 50, 'sale_started_at' => now()]);
        $this->otherProduct->update(['lowprice' => 50, 'sale_started_at' => now()]);

        $this->withSelectedSettlement()
            ->get(route('search', ['q' => 'Regional Product']))
            ->assertOk()
            ->assertSee($this->selectedProduct->name)
            ->assertDontSee($this->otherProduct->name);

        $this->withSelectedSettlement()
            ->get(route('catalog.sale'))
            ->assertOk()
            ->assertSee($this->selectedProduct->name)
            ->assertDontSee($this->otherProduct->name);
    }

    public function test_shops_and_shop_details_are_limited_to_selected_settlement(): void
    {
        $this->withSelectedSettlement()
            ->get(route('shops'))
            ->assertOk()
            ->assertSee($this->selectedSeller->name)
            ->assertDontSee($this->otherSeller->name);

        $this->withSelectedSettlement()
            ->get(route('seller', $this->otherSeller->alias))
            ->assertNotFound();
    }

    public function test_product_details_and_favorites_reject_products_from_other_settlement(): void
    {
        $this->withSelectedSettlement()
            ->get(route('product.show', $this->otherProduct->alias))
            ->assertNotFound();

        $this->withSelectedSettlement()
            ->getJson(route('favorites.products', [
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

    public function test_regional_cache_keys_are_isolated(): void
    {
        $cache = app(CatalogCacheService::class);

        $this->withSelectedSettlement()->get(route('catalog.index'))->assertOk();

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

        $this->withSelectedSettlement()
            ->get(route('catalog.index'))
            ->assertOk()
            ->assertSee($selectedCategory->name)
            ->assertDontSee($otherCategory->name);
    }

    private function withSelectedSettlement(): static
    {
        return $this->withSession(['regional_settlement_id' => $this->selectedSettlement->id]);
    }

    private function createSellerWithPackage(Settlement $settlement, string $alias): Seller
    {
        $seller = Seller::factory()->for($settlement)->create(['alias' => $alias]);
        $package = Package::factory()->withLimits(50, 10)->create();

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);

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
