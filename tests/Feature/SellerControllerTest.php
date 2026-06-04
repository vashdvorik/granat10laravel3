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
 * Feature-тест: страницы продавца (/shops, /shops/{alias}).
 *
 * @covers \App\Http\Controllers\SellerController::shops
 * @covers \App\Http\Controllers\SellerController::seller
 */
class SellerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // =========================================================================
    // GET /shops
    // =========================================================================

    public function test_shops_page_returns_200(): void
    {
        $response = $this->get(route('shops'));

        $response->assertStatus(200);
    }

    public function test_shops_page_has_sellers_variable(): void
    {
        Seller::factory()->count(2)->create();

        Cache::flush();

        $response = $this->get(route('shops'));

        $response->assertViewHas('sellers');
    }

    public function test_shops_page_shows_seller_names(): void
    {
        $seller = Seller::factory()->create(['name' => 'МагазинТестXYZUnique789']);

        Cache::flush();

        $response = $this->get(route('shops'));

        $response->assertSee('МагазинТестXYZUnique789');
    }

    public function test_shops_page_paginates_sellers(): void
    {
        Seller::factory()->count(15)->create();

        Cache::flush();

        $response = $this->get(route('shops'));

        $response->assertStatus(200);
        $sellers = $response->viewData('sellers');
        $this->assertLessThanOrEqual(12, $sellers->count());
    }

    // =========================================================================
    // GET /shops/{alias}
    // =========================================================================

    public function test_seller_page_returns_200_for_valid_seller(): void
    {
        $seller = Seller::factory()->create(['alias' => 'test-seller-page-unique']);

        $response = $this->get(route('seller', $seller->alias));

        $response->assertStatus(200);
    }

    public function test_seller_page_returns_404_for_nonexistent_alias(): void
    {
        $response = $this->get(route('seller', 'this-seller-does-not-exist-xyz999'));

        $response->assertStatus(404);
    }

    public function test_seller_page_has_required_view_variables(): void
    {
        $seller = Seller::factory()->create(['alias' => 'test-seller-vars-unique']);

        $response = $this->get(route('seller', $seller->alias));

        $response->assertViewHas('seller');
        $response->assertViewHas('products');
        $response->assertViewHas('categories');
    }

    public function test_seller_page_shows_only_visible_products_of_that_seller(): void
    {
        $package   = Package::factory()->withLimits(50, 10)->create();
        $seller1   = Seller::factory()->create(['alias' => 'seller-one-unique-test']);
        $seller2   = Seller::factory()->create();
        $category  = Category::factory()->create();

        SellerPackage::factory()->create([
            'seller_id'  => $seller1->id,
            'package_id' => $package->id,
            'is_active'  => true,
        ]);
        SellerPackage::factory()->create([
            'seller_id'  => $seller2->id,
            'package_id' => $package->id,
            'is_active'  => true,
        ]);

        Product::factory()->create([
            'seller_id'   => $seller1->id,
            'category_id' => $category->id,
            'is_active'   => true,
            'name'        => 'Seller1Product_UNIQUE_ZZZZ',
        ]);
        Product::factory()->create([
            'seller_id'   => $seller2->id,
            'category_id' => $category->id,
            'is_active'   => true,
            'name'        => 'Seller2Product_UNIQUE_ZZZZ',
        ]);

        Cache::flush();

        $response = $this->get(route('seller', $seller1->alias));

        $response->assertSee('Seller1Product_UNIQUE_ZZZZ');
        $response->assertDontSee('Seller2Product_UNIQUE_ZZZZ');
    }

    public function test_seller_page_shows_seller_description(): void
    {
        $seller = Seller::factory()->create([
            'alias'       => 'seller-desc-test-unique',
            'description' => 'Уникальное описание тест-магазина XYZ789',
        ]);

        Cache::flush();

        $response = $this->get(route('seller', $seller->alias));

        $response->assertSee('Уникальное описание тест-магазина XYZ789');
    }

    public function test_inactive_product_not_shown_on_seller_page(): void
    {
        $package  = Package::factory()->withLimits(50, 10)->create();
        $seller   = Seller::factory()->create(['alias' => 'test-inactive-product-seller']);
        $category = Category::factory()->create();

        SellerPackage::factory()->create(['seller_id' => $seller->id, 'package_id' => $package->id, 'is_active' => true]);

        Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => false,
            'name'        => 'HiddenProduct_SellerTest_UNIQUE',
        ]);

        Cache::flush();

        $response = $this->get(route('seller', $seller->alias));

        $response->assertDontSee('HiddenProduct_SellerTest_UNIQUE');
    }

    public function test_load_more_shops_returns_sellers_partial(): void
    {
        Seller::factory()->create(['name' => 'LoadMoreShopUnique']);

        $response = $this->get(route('shops.loadMore'));

        $response->assertStatus(200);
        $response->assertSee('LoadMoreShopUnique');
    }

    public function test_load_more_seller_products_returns_products_partial(): void
    {
        $package = Package::factory()->withLimits(50, 10)->create();
        $seller = Seller::factory()->create(['alias' => 'load-more-seller-products']);
        $category = Category::factory()->create();

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'LoadMoreSellerProductUnique',
        ]);

        $response = $this->get(route('seller.loadMore', $seller->alias));

        $response->assertStatus(200);
        $response->assertSee('LoadMoreSellerProductUnique');
    }
}
