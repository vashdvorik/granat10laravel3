<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Region;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\Settlement;
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
        $response->assertSee('class="shop-card"', false);
        $response->assertDontSee('shop-banner.jpg', false);
        $response->assertDontSee('class="market-section-heading"', false);
        $response->assertDontSee('id="shops-list-title"', false);
        $response->assertDontSee('class="market-count"', false);
    }

    public function test_shop_logo_fills_rounded_frame(): void
    {
        $response = $this->get(route('shops'));

        $response->assertOk();

        $css = file_get_contents(public_path('css/marketplace-design.css'));

        $this->assertStringContainsString('.shop-logo img {', $css);
        $this->assertStringContainsString('padding: 0;', $css);
        $this->assertStringContainsString('object-fit: cover;', $css);
    }

    public function test_shops_page_paginates_sellers(): void
    {
        Seller::factory()->count(15)->create();

        Cache::flush();

        $response = $this->get(route('shops'));

        $response->assertStatus(200);
        $sellers = $response->viewData('sellers');
        $this->assertLessThanOrEqual(12, $sellers->count());
        $response->assertSee('class="market-btn market-btn--secondary btn-load-more"', false);
        $response->assertDontSee('class="btn btn-load-more"', false);
    }

    public function test_regional_shops_page_has_city_switcher_for_mobile_and_desktop(): void
    {
        $region = Region::factory()->create(['name' => 'Приднестровье']);
        $currentSettlement = Settlement::factory()->for($region)->create([
            'name' => 'Слободзея',
            'slug' => 'slobodzeya',
        ]);
        $otherSettlement = Settlement::factory()->for($region)->create([
            'name' => 'Тирасполь',
            'slug' => 'tiraspol',
        ]);

        $this->get(route('regional.shops', ['location' => $currentSettlement->slug]))
            ->assertOk()
            ->assertSee('class="header-mobile-region hidden-md hidden-lg"', false)
            ->assertSee('class="shops-hero__region"', false)
            ->assertSee('regional-selector--mobile-header', false)
            ->assertSee('regional-selector--hero', false)
            ->assertSee('Слободзея')
            ->assertSee('Тирасполь')
            ->assertSee(route('regionality.update'), false)
            ->assertSee('name="settlement_id" value="'.$otherSettlement->id.'"', false)
            ->assertSee('name="return_to" value="'.route('regional.shops', ['location' => $currentSettlement->slug]).'"', false);
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
        $settlement = Settlement::factory()->create(['name' => 'Тирасполь']);
        $seller = Seller::factory()->create([
            'alias'         => 'seller-desc-test-unique',
            'description'   => 'Уникальное описание тест-магазина XYZ789',
            'settlement_id' => $settlement->id,
        ]);

        Cache::flush();

        $response = $this->get(route('seller', $seller->alias));

        $response->assertSee('Уникальное описание тест-магазина XYZ789');
        $response->assertSee('Тирасполь');
        $response->assertDontSee('Местный магазин');
        $response->assertSee('class="seller-profile__panel"', false);
        $response->assertDontSee('blog-banner.png', false);
    }

    public function test_seller_social_links_are_grouped_for_mobile(): void
    {
        $seller = Seller::factory()->create([
            'alias' => 'seller-socials-mobile',
            'telegram' => '@seller_test',
            'instagram' => '@seller_test',
            'viber' => '+37377993603',
        ]);

        $response = $this->get(route('seller', $seller->alias));

        $response->assertOk()
            ->assertSee('class="seller-socials"', false)
            ->assertSee('aria-label="', false)
            ->assertSee('aria-label="Telegram"', false)
            ->assertSee('aria-label="Instagram"', false)
            ->assertSee('aria-label="Viber"', false);

        $css = file_get_contents(public_path('css/marketplace-design.css'));

        $this->assertStringContainsString('.seller-socials {', $css);
        $this->assertStringContainsString(
            '.seller-socials { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }',
            $css
        );
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
        $response->assertSee('class="shop-card"', false);
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
        $response->assertSee('class="home-product"', false);
    }

    public function test_seller_page_keeps_desktop_pagination_and_mobile_load_more(): void
    {
        $package = Package::factory()->withLimits(50, 10)->create();
        $seller = Seller::factory()->create(['alias' => 'seller-products-pagination']);
        $category = Category::factory()->create();

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
        ]);
        Product::factory()->count(13)->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('seller', $seller->alias));

        $response->assertOk();
        $response->assertSee('id="seller-products-grid"', false);
        $response->assertSee('pagination-desktop hidden-xs hidden-sm', false);
        $response->assertSee('class="market-btn market-btn--secondary btn-load-more"', false);
        $response->assertDontSee('class="btn btn-load-more"', false);
        $response->assertSee('data-container="#seller-products-grid"', false);
        $response->assertSee(route('seller.loadMore', ['alias' => $seller->alias]), false);
    }
}
