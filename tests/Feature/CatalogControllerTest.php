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
}
