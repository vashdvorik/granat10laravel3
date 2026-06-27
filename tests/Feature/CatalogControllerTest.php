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
 * Feature-тест: публичный каталог (/catalog).
 *
 * @covers \App\Http\Controllers\CatalogController::catalog
 * @covers \App\Http\Controllers\CatalogController::show
 */
class CatalogControllerTest extends TestCase
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

    private function createVisibleProductWithSeller(?Seller $seller = null, ?Category $category = null): Product
    {
        $package  = Package::factory()->withLimits(50, 10)->create();
        $seller   = $seller ?? Seller::factory()->create();
        $category = $category ?? Category::factory()->create();

        SellerPackage::factory()->create([
            'seller_id'  => $seller->id,
            'package_id' => $package->id,
            'is_active'  => true,
            'end_date'   => null,
        ]);

        return Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
        ]);
    }

    // =========================================================================
    // GET /catalog
    // =========================================================================

    public function test_catalog_page_returns_200(): void
    {
        $response = $this->get(route('catalog.index'));

        $response->assertStatus(200);
    }

    public function test_catalog_page_has_required_view_variables(): void
    {
        $this->createVisibleProductWithSeller();

        $response = $this->get(route('catalog.index'));

        $response->assertViewHas('products');
        $response->assertViewHas('categories');
        $response->assertViewHas('sellers');
        $response->assertViewHas('minPrice');
        $response->assertViewHas('maxPrice');
    }

    public function test_catalog_shows_only_visible_products(): void
    {
        $seller   = Seller::factory()->create(); // без пакета — товар не виден
        $category = Category::factory()->create();

        Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
            'name'        => 'SecretProduct_NotVisible_XYZ',
        ]);

        $response = $this->get(route('catalog.index'));

        $response->assertDontSee('SecretProduct_NotVisible_XYZ');
    }

    public function test_catalog_shows_visible_product_name(): void
    {
        $this->createVisibleProductWithSeller(
            category: Category::factory()->create()
        );
        // Создаём виден товар с уникальным именем
        $package  = Package::factory()->withLimits(50, 10)->create();
        $seller   = Seller::factory()->create();
        $category = Category::factory()->create();
        SellerPackage::factory()->create(['seller_id' => $seller->id, 'package_id' => $package->id, 'is_active' => true, 'end_date' => null]);
        Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
            'name'        => 'VisibleProductName_UNIQUE_ZZZ',
        ]);

        Cache::flush();

        $response = $this->get(route('catalog.index'));

        $response->assertSee('VisibleProductName_UNIQUE_ZZZ');
        $response->assertSee('class="home-product"', false);
        $response->assertDontSee('class="col-xs-6 col-sm-6 col-md-4 col-lg-4 product-item"', false);
    }

    public function test_catalog_paginates_products(): void
    {
        $package  = Package::factory()->withLimits(50, 10)->create();
        $seller   = Seller::factory()->create();
        $category = Category::factory()->create();
        SellerPackage::factory()->create(['seller_id' => $seller->id, 'package_id' => $package->id, 'is_active' => true]);

        Product::factory()->count(15)->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
        ]);

        Cache::flush();

        $response = $this->get(route('catalog.index'));

        $response->assertStatus(200);
        // Пагинация 12 товаров на страницу — первая страница не должна содержать все 15
        $products = $response->viewData('products');
        $this->assertLessThanOrEqual(12, $products->count());
        $response->assertSee('class="market-btn market-btn--secondary btn-load-more"', false);
        $response->assertDontSee('class="btn btn-load-more"', false);
    }

    // =========================================================================
    // GET /catalog/{alias} — категория
    // =========================================================================

    public function test_category_catalog_page_returns_200(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $response = $this->get(route('catalog.show', $category->alias));

        $response->assertStatus(200);
    }

    public function test_category_catalog_returns_404_for_inactive_category(): void
    {
        $category = Category::factory()->create(['is_active' => false]);

        $response = $this->get(route('catalog.show', $category->alias));

        $response->assertStatus(404);
    }

    public function test_category_catalog_has_breadcrumbs(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $response = $this->get(route('catalog.show', $category->alias));

        $response->assertViewHas('breadcrumbs');
        $response->assertViewHas('category');
    }

    public function test_category_catalog_shows_only_products_in_category(): void
    {
        $package   = Package::factory()->withLimits(50, 10)->create();
        $seller    = Seller::factory()->create();
        $category1 = Category::factory()->create(['is_active' => true]);
        $category2 = Category::factory()->create(['is_active' => true]);

        SellerPackage::factory()->create(['seller_id' => $seller->id, 'package_id' => $package->id, 'is_active' => true]);

        Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category1->id,
            'is_active'   => true,
            'name'        => 'InCategory1_Product_UNIQ',
        ]);

        Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category2->id,
            'is_active'   => true,
            'name'        => 'InCategory2_Product_UNIQ',
        ]);

        Cache::flush();

        $response = $this->get(route('catalog.show', $category1->alias));

        $response->assertSee('InCategory1_Product_UNIQ');
        $response->assertDontSee('InCategory2_Product_UNIQ');
    }

    // =========================================================================
    // Фильтрация по цене
    // =========================================================================

    public function test_catalog_price_filter_works(): void
    {
        $package  = Package::factory()->withLimits(50, 10)->create();
        $seller   = Seller::factory()->create();
        $category = Category::factory()->create();
        SellerPackage::factory()->create(['seller_id' => $seller->id, 'package_id' => $package->id, 'is_active' => true]);

        $cheapProduct     = Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'is_active' => true, 'price' => 100, 'name' => 'Cheap_100_Unique']);
        $expensiveProduct = Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'is_active' => true, 'price' => 9999, 'name' => 'Expensive_9999_Unique']);

        $response = $this->get(route('catalog.index', ['min_price' => 50, 'max_price' => 200]));

        $response->assertStatus(200);
    }

    public function test_catalog_sort_by_lowest_price_first(): void
    {
        $package = Package::factory()->withLimits(50, 10)->create();
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        SellerPackage::factory()->create(['seller_id' => $seller->id, 'package_id' => $package->id, 'is_active' => true]);

        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'ExpensiveSortedProduct',
            'price' => 900,
            'lowprice' => null,
        ]);
        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'CheapSortedProduct',
            'price' => 100,
            'lowprice' => null,
        ]);

        $response = $this->get(route('catalog.index', ['sort' => 'price_asc']));

        $names = $response->viewData('products')->pluck('name')->values();
        $this->assertSame('CheapSortedProduct', $names->first());
        $response->assertSee('Сначала дешевые');
    }

    public function test_catalog_sort_by_sale_first(): void
    {
        $package = Package::factory()->withLimits(50, 10)->create();
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        SellerPackage::factory()->create(['seller_id' => $seller->id, 'package_id' => $package->id, 'is_active' => true]);

        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'RegularSortedProduct',
            'price' => 100,
            'lowprice' => null,
        ]);
        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'SaleSortedProduct',
            'price' => 1000,
            'lowprice' => 700,
            'sale_started_at' => now(),
        ]);

        $response = $this->get(route('catalog.index', ['sort' => 'sale']));

        $names = $response->viewData('products')->pluck('name')->values();
        $this->assertSame('SaleSortedProduct', $names->first());
        $response->assertSee('Сначала акции');
    }

    public function test_load_more_catalog_returns_products_partial(): void
    {
        $this->createVisibleProductWithSeller();
        $package  = Package::factory()->withLimits(50, 10)->create();
        $seller   = Seller::factory()->create();
        $category = Category::factory()->create();
        SellerPackage::factory()->create(['seller_id' => $seller->id, 'package_id' => $package->id, 'is_active' => true, 'end_date' => null]);
        Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
            'name'        => 'LoadMoreCatalogProduct_UNIQ',
        ]);

        $response = $this->get(route('catalog.loadMore'));

        $response->assertStatus(200);
        $response->assertSee('LoadMoreCatalogProduct_UNIQ');
        $response->assertSee('class="home-product"', false);
    }

    public function test_load_more_category_returns_products_partial(): void
    {
        $package   = Package::factory()->withLimits(50, 10)->create();
        $seller    = Seller::factory()->create();
        $category1 = Category::factory()->create(['is_active' => true]);
        $category2 = Category::factory()->create(['is_active' => true]);
        SellerPackage::factory()->create(['seller_id' => $seller->id, 'package_id' => $package->id, 'is_active' => true, 'end_date' => null]);
        Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category1->id,
            'is_active'   => true,
            'name'        => 'LoadMoreCategoryProduct_UNIQ',
        ]);
        Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category2->id,
            'is_active'   => true,
            'name'        => 'LoadMoreOtherCategoryProduct_UNIQ',
        ]);

        $response = $this->get(route('catalog.loadMoreCategory', $category1->alias));

        $response->assertStatus(200);
        $response->assertSee('LoadMoreCategoryProduct_UNIQ');
        $response->assertDontSee('LoadMoreOtherCategoryProduct_UNIQ');
        $response->assertSee('class="home-product"', false);
    }

    public function test_load_more_sale_returns_products_partial(): void
    {
        $package  = Package::factory()->withLimits(50, 10)->create();
        $seller   = Seller::factory()->create();
        $category = Category::factory()->create();
        SellerPackage::factory()->create(['seller_id' => $seller->id, 'package_id' => $package->id, 'is_active' => true, 'end_date' => null]);
        Product::factory()->create([
            'seller_id'       => $seller->id,
            'category_id'     => $category->id,
            'is_active'       => true,
            'name'            => 'LoadMoreSaleProduct_UNIQ',
            'lowprice'        => 90,
            'sale_started_at' => now(),
        ]);

        $response = $this->get(route('catalog.loadMoreSale'));

        $response->assertStatus(200);
        $response->assertSee('LoadMoreSaleProduct_UNIQ');
        $response->assertSee('class="home-product"', false);
    }

    public function test_sale_page_uses_modern_product_cards(): void
    {
        $package = Package::factory()->withLimits(50, 10)->create();
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        SellerPackage::factory()->create(['seller_id' => $seller->id, 'package_id' => $package->id, 'is_active' => true, 'end_date' => null]);
        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'SalePageModernCardProduct',
            'price' => 1000,
            'lowprice' => 700,
            'sale_started_at' => now(),
        ]);

        $response = $this->get(route('catalog.sale'));

        $response->assertOk();
        $response->assertSee('sale-products-grid', false);
        $response->assertSee('class="home-product"', false);
        $response->assertSee('SalePageModernCardProduct');
        $response->assertDontSee('class="col-xs-6 col-sm-6 col-md-4 col-lg-4 product-item"', false);
        $response->assertDontSee('shop-banner.jpg');
        $response->assertDontSee('<h1 class="cate-title">Featured Products</h1>', false);
        $response->assertDontSee('<h1 class="cate-title">Top Rated Products</h1>', false);
        $response->assertDontSee('<h1 class="cate-title">Top Selling Products</h1>', false);

        $css = file_get_contents(public_path('css/marketplace-design.css'));
        $this->assertStringContainsString('.sale-page .sale-products-grid.product-collection-grid', $css);
        $this->assertStringContainsString('margin-top: 14px;', $css);
    }

    public function test_sale_page_uses_market_load_more_button(): void
    {
        $package = Package::factory()->withLimits(50, 10)->create();
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        SellerPackage::factory()->create(['seller_id' => $seller->id, 'package_id' => $package->id, 'is_active' => true, 'end_date' => null]);
        Product::factory()->count(13)->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
            'lowprice' => 700,
            'sale_started_at' => now(),
        ]);

        $response = $this->get(route('catalog.sale'));

        $response->assertOk();
        $response->assertSee('class="market-btn market-btn--secondary btn-load-more"', false);
        $response->assertDontSee('class="btn btn-load-more"', false);
    }

    public function test_load_more_search_returns_products_partial(): void
    {
        $package  = Package::factory()->withLimits(50, 10)->create();
        $seller   = Seller::factory()->create();
        $category = Category::factory()->create();
        SellerPackage::factory()->create(['seller_id' => $seller->id, 'package_id' => $package->id, 'is_active' => true, 'end_date' => null]);
        Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
            'name'        => 'LoadMoreSearchProduct_UNIQ',
        ]);

        $response = $this->get(route('search.loadMore', ['q' => 'LoadMoreSearch']));

        $response->assertStatus(200);
        $response->assertSee('LoadMoreSearchProduct_UNIQ');
    }

    public function test_search_page_uses_market_load_more_button(): void
    {
        $package = Package::factory()->withLimits(50, 10)->create();
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        SellerPackage::factory()->create(['seller_id' => $seller->id, 'package_id' => $package->id, 'is_active' => true, 'end_date' => null]);
        Product::factory()->count(13)->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'MarketLoadMoreSearchProduct',
        ]);

        $response = $this->get(route('search', ['q' => 'MarketLoadMoreSearch']));

        $response->assertOk();
        $response->assertSee('class="market-btn market-btn--secondary btn-load-more"', false);
        $response->assertDontSee('class="btn btn-load-more"', false);
    }

    public function test_search_page_accepts_cyrillic_query(): void
    {
        $product = $this->createVisibleProductWithSeller();
        $product->update(['name' => 'Беспроводные наушники']);

        $response = $this->get(route('search', ['q' => 'наушники']));

        $response->assertOk();
        $response
            ->assertSee('Беспроводные наушники')
            ->assertSee('class="market-search search-page-form"', false)
            ->assertSee('id="search-page-query"', false)
            ->assertSee('--market-search-action: #8750b4', false)
            ->assertSee('background: linear-gradient(90deg, #b965ed, #4ed9ee)', false)
            ->assertSee('class="home-product"', false)
            ->assertSee('search-products-grid', false)
            ->assertDontSee('#ff7a00', false)
            ->assertDontSee('class="col-xs-6 col-sm-6 col-md-4 col-lg-4 product-item"', false);
    }
}
